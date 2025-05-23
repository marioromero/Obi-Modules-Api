<?php

namespace Modules\Core\app\Services;

use Carbon\Carbon;
use Modules\Core\App\Support\Services\ServiceHandlerException;
use Modules\Core\App\Support\DTO\ServiceResponseDTO;

class MindicadorService
{
    private string $base;
    private ServiceHandlerException $handler;

    public function __construct(ServiceHandlerException $handler)
    {
        $this->base = config('services.mindicador.base_uri', 'https://mindicador.cl/api');
        $this->handler = $handler;
    }

    public function getCurrentUf(): ServiceResponseDTO
    {
        $url = "{$this->base}/uf";
        // 1) Llamada genérica
        $response = $this->handler->fetchJson($url, 'serie', 'UF obtenida correctamente');

        // 2) Si el handler devolvió error (502, 503, SSL, etc.), reenvíalo tal cual
        if (!$response->success) {
            return $response;
        }

        // 3) Si no hubo datos en la serie, fallo de negocio 404
        if (empty($response->data)) {
            return ServiceResponseDTO::fail(
                'No hay datos disponibles de UF',
                404
            );
        }

        // 4) Extrae el valor del primer elemento
        $valor = $response->data[0]['valor'] ?? null;

        // 5) Si por alguna razón falta el campo 'valor'
        if (is_null($valor)) {
            return ServiceResponseDTO::fail(
                'Formato de datos inesperado desde el servicio externo',
                500
            );
        }

        // 6) retorno sólo el float de la UF
        return ServiceResponseDTO::ok(
            $valor,
            'UF actual obtenida correctamente'
        );
    }

    /**
     * Obtiene la UF para una fecha específica (dd-mm-aaaa).
     *
     * @param \DateTimeInterface|string $date
     * @return ServiceResponseDTO
     */
    public function getUfByDate(\DateTimeInterface|string $date): ServiceResponseDTO
    {
        // 1) Convertir a Carbon y formatear como dd-mm-YYYY
        $dt = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date);
        $ddmmyyyy = $dt->format('d-m-Y');

        // 2) Llamada genérica al handler
        $url = "{$this->base}/uf/{$ddmmyyyy}";
        $response = $this->handler->fetchJson($url, 'serie', "UF para el {$ddmmyyyy}");

        // 3) Si hubo error de infraestructura, reenvíalo
        if (!$response->success) {
            return $response;
        }

        // 4) Si la serie viene vacía, fallo de negocio 404
        if (empty($response->data)) {
            return ServiceResponseDTO::fail(
                "No hay datos de UF para la fecha {$ddmmyyyy}",
                404
            );
        }

        // 5) Extraer el valor del primer elemento
        $valor = $response->data[0]['valor'] ?? null;
        if (is_null($valor)) {
            return ServiceResponseDTO::fail(
                'Formato inesperado desde el servicio externo',
                500
            );
        }

        // 6) Retornar el valor
        return ServiceResponseDTO::ok(
            $valor,
            "UF para el {$ddmmyyyy}"
        );
    }

}
