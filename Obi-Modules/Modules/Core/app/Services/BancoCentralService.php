<?php

namespace Modules\Core\app\Services;

use Carbon\Carbon;
use Modules\Core\app\Support\DTO\ServiceResponseDTO;
use Modules\Core\app\Support\Services\ServiceHandlerException;

class BancoCentralService
{
    private string $base;

    private string $token;

    private string $ufSeries;

    private float $timeout;

    private ServiceHandlerException $handler;

    public function __construct(ServiceHandlerException $handler)
    {
        $this->base = config('services.bcentral.base_uri', 'https://si3.bcentral.cl/SieteRestWS/SieteRestWS.ashx');
        $this->token = (string) config('services.bcentral.token', '');
        $this->ufSeries = (string) config('services.bcentral.uf_series', 'F073.UFF.PRE.Z.D');
        $this->timeout = (float) config('services.bcentral.timeout', 10);
        $this->handler = $handler;
    }

    /**
     * Obtiene la UF del día en curso desde el Banco Central.
     */
    public function getCurrentUf(): ServiceResponseDTO
    {
        return $this->getUfByDate(Carbon::now());
    }

    /**
     * Obtiene la UF para una fecha específica desde el Banco Central.
     */
    public function getUfByDate(\DateTimeInterface|string $date): ServiceResponseDTO
    {
        // 1) Convertir a Carbon y formatear como YYYY-MM-DD (exigido por la API)
        $dt = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date);
        $ymd = $dt->format('Y-m-d');

        // 2) Construir la URL con los parámetros requeridos
        $query = http_build_query([
            'token' => $this->token,
            'function' => 'GetSeries',
            'timeseries' => $this->ufSeries,
            'firstdate' => $ymd,
            'lastdate' => $ymd,
        ]);
        $url = "{$this->base}?{$query}";

        // 3) Llamada genérica al handler, extrayendo Series.Obs
        $response = $this->handler->fetchJson($url, 'Series.Obs', "UF para el {$ymd}", $this->timeout);

        // 4) Si hubo error de infraestructura, reenvíalo
        if (! $response->success) {
            return $response;
        }

        // 5) Si Obs viene vacío, fallo de negocio 404
        if (empty($response->data)) {
            return ServiceResponseDTO::fail(
                "No hay datos de UF para la fecha {$ymd}",
                404
            );
        }

        // 6) Extraer y validar el valor
        $valor = $response->data[0]['value'] ?? null;
        if (is_null($valor) || ! is_numeric($valor)) {
            return ServiceResponseDTO::fail(
                'Formato inesperado desde el servicio externo',
                500
            );
        }

        // 7) Retornar el valor como float
        return ServiceResponseDTO::ok(
            (float) $valor,
            "UF para el {$ymd} (Banco Central)"
        );
    }

    /**
     * Obtiene todas las observaciones de UF en un rango de fechas.
     *
     * @return ServiceResponseDTO data = [['fecha' => 'Y-m-d', 'valor' => float], ...]
     */
    public function getUfRange(\DateTimeInterface|string $from, \DateTimeInterface|string $to): ServiceResponseDTO
    {
        $fromDate = $from instanceof \DateTimeInterface ? Carbon::instance($from) : Carbon::parse($from);
        $toDate = $to instanceof \DateTimeInterface ? Carbon::instance($to) : Carbon::parse($to);

        if ($fromDate->gt($toDate)) {
            return ServiceResponseDTO::fail('Rango de fechas inválido', 400);
        }

        $query = http_build_query([
            'token' => $this->token,
            'function' => 'GetSeries',
            'timeseries' => $this->ufSeries,
            'firstdate' => $fromDate->format('Y-m-d'),
            'lastdate' => $toDate->format('Y-m-d'),
        ]);
        $url = "{$this->base}?{$query}";

        $response = $this->handler->fetchJson(
            $url,
            'Series.Obs',
            "UF desde {$fromDate->format('Y-m-d')} hasta {$toDate->format('Y-m-d')}",
            $this->timeout
        );

        if (! $response->success) {
            return $response;
        }

        if (empty($response->data) || ! is_array($response->data)) {
            return ServiceResponseDTO::fail(
                "No hay datos de UF entre {$fromDate->format('Y-m-d')} y {$toDate->format('Y-m-d')}",
                404
            );
        }

        $observations = [];

        foreach ($response->data as $item) {
            if (! is_array($item) || ! isset($item['value']) || ! is_numeric($item['value'])) {
                continue;
            }

            $date = $this->normalizeDate($item['indexDateString'] ?? null);

            if ($date === null) {
                continue;
            }

            $observations[$date] = [
                'fecha' => $date,
                'valor' => (float) $item['value'],
            ];
        }

        if (empty($observations)) {
            return ServiceResponseDTO::fail(
                "No hay datos de UF entre {$fromDate->format('Y-m-d')} y {$toDate->format('Y-m-d')}",
                404
            );
        }

        ksort($observations);

        return ServiceResponseDTO::ok(array_values($observations));
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        try {
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value) === 1) {
                return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
