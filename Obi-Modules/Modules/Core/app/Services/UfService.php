<?php

namespace Modules\Core\app\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Carbon;
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
     * Obtiene la UF para una fecha específica (dd-mm-aaaa).
     *
     * Consulta mindicador.cl con un límite de 5 segundos; si la respuesta
     * sobrepasa ese plazo (timeout), la conexión falla o el servicio
     * responde con error, consulta el Banco Central de Chile.
     */
    public function getUfByDate(\DateTimeInterface|string $date): ServiceResponseDTO
    {
        return $this->withFallback(
            fn () => $this->mindicadorService->getUfByDate($date),
            fn () => $this->bancoCentralService->getUfByDate($date)
        );
    }

    /**
     * Obtiene la UF consultando mindicador y Banco Central en paralelo.
     * Timeout mindicador: 3s. Timeout Banco Central: 4s.
     * Devuelve siempre un array con el contrato del dashboard.
     */
    public function getUfDashboard(string $date): array
    {
        $dt = Carbon::parse($date);
        $ymd = $dt->format('Y-m-d');
        $ddmmyyyy = $dt->format('d-m-Y');

        $client = new Client(['verify' => false]);

        $promises = [
            'mindicador' => $client->getAsync("https://mindicador.cl/api/uf/{$ddmmyyyy}", [
                'timeout' => 3,
            ]),
            'obi' => $client->getAsync('https://si3.bcentral.cl/SieteRestWS/SieteRestWS.ashx', [
                'query' => [
                    'token' => config('services.bcentral.token'),
                    'function' => 'GetSeries',
                    'timeseries' => 'F073.UFF.PRE.Z.D',
                    'firstdate' => $ymd,
                    'lastdate' => $ymd,
                ],
                'timeout' => 4,
            ]),
        ];

        $results = Utils::settle($promises)->wait();

        $mindicadorValor = null;
        $mindicadorFallado = false;
        $obiValor = null;
        $obiFallado = false;

        foreach ($results as $name => $result) {
            if ($result['state'] !== 'fulfilled') {
                if ($name === 'mindicador') {
                    $mindicadorFallado = true;
                } else {
                    $obiFallado = true;
                }
                continue;
            }

            $response = $result['value'];
            $body = json_decode($response->getBody()->getContents(), true);

            if ($name === 'mindicador') {
                if (!empty($body['serie'][0]['valor'])) {
                    $mindicadorValor = (float) $body['serie'][0]['valor'];
                } else {
                    $mindicadorFallado = true;
                }
            } else {
                if (!empty($body['Series']['Obs'][0]['value'])) {
                    $obiValor = (float) $body['Series']['Obs'][0]['value'];
                } else {
                    $obiFallado = true;
                }
            }
        }

        $valor = $mindicadorValor ?? $obiValor;

        if ($valor !== null) {
            return [
                'success' => true,
                'valor' => $valor,
                'fecha' => $ymd,
            ];
        }

        return [
            'success' => false,
            'error' => [
                'cod' => 'UF_NO_DISPONIBLE',
                'mensaje' => 'No se pudo obtener el valor de la UF. Intente nuevamente más tarde.',
                'motivo' => $this->buildMotivo($mindicadorFallado, $obiFallado),
                'fecha' => $ymd,
            ],
        ];
    }

    private function buildMotivo(bool $mindicadorFallado, bool $obiFallado): string
    {
        $partes = [];
        if ($mindicadorFallado) {
            $partes[] = 'mindicador';
        }
        if ($obiFallado) {
            $partes[] = 'Banco Central';
        }

        if (empty($partes)) {
            return 'servicios sin datos';
        }

        return implode(' y ', $partes) . ' sin respuesta';
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
