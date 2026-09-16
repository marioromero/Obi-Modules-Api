<?php

namespace Modules\Core\app\Services;

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

    private function withFallback(callable $primary, callable $fallback): ServiceResponseDTO
    {
        $response = $primary();

        if (! $response->success && in_array($response->code, self::FALLBACK_CODES, true)) {
            $response = $fallback();
        }

        return $response;
    }
}
