<?php

namespace Modules\Core\app\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Core\app\Support\DTO\ServiceResponseDTO;
use Modules\Core\app\Support\Services\ServiceHandlerException;

class UfService
{
    private const FALLBACK_CODES = [
        502,
        503,
        ServiceHandlerException::UPSTREAM_TIMEOUT_CODE,
    ];

    public function __construct(
        private readonly MindicadorService $mindicadorService,
        private readonly BancoCentralService $bancoCentralService
    ) {}

    public function getCurrentUf(): ServiceResponseDTO
    {
        return $this->withFallback(
            fn () => $this->mindicadorService->getCurrentUf(),
            fn () => $this->bancoCentralService->getCurrentUf()
        );
    }

    /**
     * Obtiene la UF para una fecha específica.
     *
     * Solo se acepta una observación cuya fecha coincida con la solicitada.
     * Si la fuente principal devuelve el último valor disponible con otra
     * fecha, se descarta y se consulta el Banco Central. Para fines de semana
     * o feriados se admite la última UF publicada dentro de una ventana acotada.
     */
    public function getUfByDate(\DateTimeInterface|string $date): ServiceResponseDTO
    {
        $result = $this->resolveUf($date);

        if ($result['success']) {
            $message = "UF para el {$result['fecha']}";

            if ($result['fuente'] === 'banco_central') {
                $message .= ' (Banco Central)';
            }

            if ($result['ultima_publicada']) {
                $message .= ' (última UF publicada)';
            }

            return ServiceResponseDTO::ok($result['valor'], $message);
        }

        return ServiceResponseDTO::fail(
            $result['error']['mensaje'],
            $result['error']['code']
        );
    }

    /**
     * Obtiene la UF para el dashboard.
     *
     * Devuelve siempre un array con el contrato del dashboard. Incluye
     * `fecha_valor` y `fuente` para poder diagnosticar qué observación se usó.
     */
    public function getUfDashboard(string $date): array
    {
        $result = $this->resolveUf($date);

        if ($result['success']) {
            return [
                'success' => true,
                'valor' => $result['valor'],
                'fecha' => $result['fecha'],
                'fecha_valor' => $result['fecha_valor'],
                'fuente' => $result['fuente'],
                'ultima_publicada' => $result['ultima_publicada'],
            ];
        }

        return [
            'success' => false,
            'error' => [
                'cod' => 'UF_NO_DISPONIBLE',
                'mensaje' => 'No se pudo obtener el valor de la UF. Intente nuevamente más tarde.',
                'motivo' => $result['error']['motivo'],
                'fecha' => $result['fecha'],
            ],
        ];
    }

    private function resolveUf(\DateTimeInterface|string $date): array
    {
        $requested = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date);
        $requested = $requested->copy()->startOfDay();
        $ymd = $requested->format('Y-m-d');

        $cacheKey = "uf:valor:v2:{$ymd}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchUf($requested, $ymd);

        $ttl = $result['success']
            ? $this->cacheTtlFor($result, $requested)
            : max(0, (int) config('services.uf.cache_ttl_error', 30));

        if ($ttl > 0) {
            Cache::put($cacheKey, $result, now()->addSeconds($ttl));
        }

        return $result;
    }

    private function fetchUf(Carbon $requested, string $ymd): array
    {
        $lookbackDays = max(0, (int) config('services.uf.lookback_days', 7));

        $mindicador = $this->fetchMindicador($requested);

        // Una observación sin fecha se considera de la fecha solicitada
        // (compatibilidad con respuestas antiguas); si trae fecha, debe coincidir.
        if ($mindicador['status'] === 'ok'
            && ($mindicador['fecha'] === null || $mindicador['fecha'] === $ymd)) {
            return $this->successResult(
                $mindicador['valor'],
                $ymd,
                $mindicador['fecha'] ?? $ymd,
                'mindicador',
                false
            );
        }

        $bancoCentral = $this->fetchBancoCentral($requested, $requested);
        $exact = $this->pickExact($bancoCentral['observations'], $ymd);

        if ($exact !== null) {
            return $this->successResult($exact['valor'], $ymd, $exact['fecha'], 'banco_central', false);
        }

        // La última UF publicada solo es válida para días sin publicación
        // (fines de semana o feriados configurados). En un día hábil, un
        // valor de otra fecha es un dato viejo y no debe reutilizarse.
        if (! $this->canUseLastPublished($requested)) {
            return $this->errorResult($mindicador, $bancoCentral, null, $ymd);
        }

        // La fuente puede devolver la observación anterior cuando la fecha
        // solicitada no tiene publicación. Solo se acepta dentro de la ventana.
        $latest = $this->pickLatestOnOrBefore($bancoCentral['observations'], $ymd);

        if ($latest !== null && $this->isWithinLookback($latest['fecha'], $ymd, $lookbackDays)) {
            return $this->successResult($latest['valor'], $ymd, $latest['fecha'], 'banco_central', true);
        }

        $rangeAttempted = false;
        $range = ['status' => 'empty', 'timeout' => false, 'observations' => []];

        if ($lookbackDays > 0) {
            $rangeAttempted = true;
            $range = $this->fetchBancoCentral($requested->copy()->subDays($lookbackDays), $requested);

            $rangeExact = $this->pickExact($range['observations'], $ymd);
            if ($rangeExact !== null) {
                return $this->successResult($rangeExact['valor'], $ymd, $rangeExact['fecha'], 'banco_central', false);
            }

            $rangeLatest = $this->pickLatestOnOrBefore($range['observations'], $ymd);
            if ($rangeLatest !== null && $this->isWithinLookback($rangeLatest['fecha'], $ymd, $lookbackDays)) {
                return $this->successResult($rangeLatest['valor'], $ymd, $rangeLatest['fecha'], 'banco_central', true);
            }
        }

        // Último recurso: valor de mindicador de una fecha anterior, siempre
        // que esté dentro de la ventana permitida. Nunca se devuelve un valor
        // viejo como si correspondiera a la fecha solicitada.
        if ($mindicador['status'] === 'ok'
            && $mindicador['fecha'] !== null
            && $this->isWithinLookback($mindicador['fecha'], $ymd, $lookbackDays)) {
            return $this->successResult(
                $mindicador['valor'],
                $ymd,
                $mindicador['fecha'],
                'mindicador',
                true
            );
        }

        return $this->errorResult($mindicador, $bancoCentral, $rangeAttempted ? $range : null, $ymd);
    }

    private function fetchMindicador(Carbon $date): array
    {
        $base = rtrim((string) config('services.mindicador.base_uri', 'https://mindicador.cl/api'), '/');
        $url = "{$base}/uf/{$date->format('d-m-Y')}";

        try {
            $response = Http::acceptJson()
                ->withOptions(['verify' => false])
                ->timeout((float) config('services.mindicador.timeout', 5))
                ->get($url);

            if ($response->failed()) {
                return $this->failedFetch(false);
            }

            $item = $response->json('serie.0');

            if (! is_array($item) || ! isset($item['valor']) || ! is_numeric($item['valor'])) {
                return ['status' => 'empty', 'timeout' => false, 'valor' => null, 'fecha' => null];
            }

            return [
                'status' => 'ok',
                'timeout' => false,
                'valor' => (float) $item['valor'],
                'fecha' => $this->normalizeObservationDate($item['fecha'] ?? null),
            ];
        } catch (ConnectionException $e) {
            return $this->failedFetch($this->isTimeout($e));
        } catch (\Throwable $e) {
            return $this->failedFetch(false);
        }
    }

    private function fetchBancoCentral(Carbon $from, Carbon $to): array
    {
        try {
            $response = Http::acceptJson()
                ->withOptions(['verify' => false])
                ->timeout((float) config('services.bcentral.timeout', 10))
                ->get(
                    (string) config('services.bcentral.base_uri', 'https://si3.bcentral.cl/SieteRestWS/SieteRestWS.ashx'),
                    [
                        'token' => config('services.bcentral.token'),
                        'function' => 'GetSeries',
                        'timeseries' => (string) config('services.bcentral.uf_series', 'F073.UFF.PRE.Z.D'),
                        'firstdate' => $from->format('Y-m-d'),
                        'lastdate' => $to->format('Y-m-d'),
                    ]
                );

            if ($response->failed()) {
                return $this->failedFetch(false);
            }

            $obs = $response->json('Series.Obs');
            $observations = [];

            if (is_array($obs)) {
                foreach ($obs as $item) {
                    if (! is_array($item) || ! isset($item['value']) || ! is_numeric($item['value'])) {
                        continue;
                    }

                    $observationDate = $this->normalizeObservationDate($item['indexDateString'] ?? null);

                    // Respuestas antiguas sin fecha: si se consultó un solo
                    // día, se asume que la observación corresponde a ese día.
                    if ($observationDate === null && $from->isSameDay($to)) {
                        $observationDate = $to->format('Y-m-d');
                    }

                    if ($observationDate === null) {
                        continue;
                    }

                    $observations[] = [
                        'valor' => (float) $item['value'],
                        'fecha' => $observationDate,
                    ];
                }
            }

            return [
                'status' => empty($observations) ? 'empty' : 'ok',
                'timeout' => false,
                'observations' => $observations,
            ];
        } catch (ConnectionException $e) {
            return $this->failedFetch($this->isTimeout($e));
        } catch (\Throwable $e) {
            return $this->failedFetch(false);
        }
    }

    private function failedFetch(bool $timeout): array
    {
        return [
            'status' => 'failed',
            'timeout' => $timeout,
            'observations' => [],
        ];
    }

    private function pickExact(array $observations, string $ymd): ?array
    {
        foreach ($observations as $observation) {
            if ($observation['fecha'] === $ymd) {
                return $observation;
            }
        }

        return null;
    }

    private function pickLatestOnOrBefore(array $observations, string $ymd): ?array
    {
        $latest = null;

        foreach ($observations as $observation) {
            if ($observation['fecha'] > $ymd) {
                continue;
            }

            if ($latest === null || $observation['fecha'] > $latest['fecha']) {
                $latest = $observation;
            }
        }

        return $latest;
    }

    private function cacheTtlFor(array $result, Carbon $requested): int
    {
        // Un valor "última publicada" puede quedar obsoleto en cuanto la
        // fuente publique el dato exacto: se cachea por poco tiempo.
        if (! empty($result['ultima_publicada'])) {
            return max(0, (int) config('services.uf.cache_ttl_last_published', 300));
        }

        $ageInDays = (time() - $requested->getTimestamp()) / 86400;

        if ($ageInDays >= 2) {
            return max(0, (int) config('services.uf.cache_ttl_history', 86400));
        }

        return max(0, (int) config('services.uf.cache_ttl_recent', 900));
    }

    private function canUseLastPublished(Carbon $requested): bool
    {
        if ($requested->isSaturday() || $requested->isSunday()) {
            return true;
        }

        $holidays = config('services.uf.holidays', []);

        if (! is_array($holidays)) {
            $holidays = [$holidays];
        }

        return in_array($requested->format('Y-m-d'), $holidays, true);
    }

    private function isWithinLookback(string $observationDate, string $requestedDate, int $lookbackDays): bool
    {
        if ($observationDate === $requestedDate) {
            return true;
        }

        $days = (strtotime($requestedDate) - strtotime($observationDate)) / 86400;

        return $days >= 0 && $days <= $lookbackDays;
    }

    private function normalizeObservationDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return substr($value, 0, 10);
        }

        try {
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value) === 1) {
                return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function successResult(
        float $valor,
        string $ymd,
        string $valorFecha,
        string $fuente,
        bool $ultimaPublicada
    ): array {
        return [
            'success' => true,
            'valor' => $valor,
            'fecha' => $ymd,
            'fecha_valor' => $valorFecha,
            'fuente' => $fuente,
            'ultima_publicada' => $ultimaPublicada,
        ];
    }

    private function errorResult(
        array $mindicador,
        array $bancoCentral,
        ?array $range,
        string $ymd
    ): array {
        $parts = [];

        if ($mindicador['status'] === 'failed') {
            $parts[] = 'mindicador';
        }

        $bcchFailed = $bancoCentral['status'] === 'failed'
            && ($range === null || $range['status'] === 'failed');

        if ($bcchFailed) {
            $parts[] = 'Banco Central';
        }

        $motivo = empty($parts)
            ? 'sin datos para la fecha solicitada'
            : implode(' y ', $parts).' sin respuesta';

        $allTimedOut = $mindicador['status'] === 'failed'
            && $mindicador['timeout']
            && $bancoCentral['status'] === 'failed'
            && $bancoCentral['timeout']
            && ($range === null || ($range['status'] === 'failed' && $range['timeout']));

        return [
            'success' => false,
            'fecha' => $ymd,
            'error' => [
                'cod' => 'UF_NO_DISPONIBLE',
                'mensaje' => 'No se pudo obtener el valor de la UF para la fecha solicitada.',
                'motivo' => $motivo,
                'code' => $allTimedOut ? ServiceHandlerException::UPSTREAM_TIMEOUT_CODE : 404,
            ],
        ];
    }

    private function isTimeout(ConnectionException $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'timed out')
            || str_contains($message, 'timeout');
    }

    private function withFallback(callable $primary, callable $fallback): ServiceResponseDTO
    {
        $response = $primary();

        if (! $response->success && in_array($response->code, self::FALLBACK_CODES, true)) {
            $response = $fallback();
        }

        return $response;
    }
}
