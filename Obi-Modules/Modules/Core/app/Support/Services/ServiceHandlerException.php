<?php

namespace Modules\Core\app\Support\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Modules\Core\app\Support\DTO\ServiceResponseDTO;
use Throwable;

class ServiceHandlerException
{
    public const UPSTREAM_TIMEOUT_CODE = 504;

    // $jsonkey es la clave que queremos extraer del servicio externo
    public function fetchJson(string $url, string $jsonKey, string $successMessage, ?float $timeout = null): ServiceResponseDTO
    {
        try {
            $request = Http::acceptJson()
                ->withOptions(['verify' => false]);

            if (! is_null($timeout)) {
                $request = $request->timeout($timeout);
            }

            $payload = $request
                ->get($url)
                ->throw()
                ->json($jsonKey);

            return ServiceResponseDTO::ok($payload, $successMessage);
        } catch (ConnectionException $e) {
            if ($this->isTimeout($e)) {
                return ServiceResponseDTO::fail(
                    'Tiempo de espera agotado consultando el servicio externo',
                    self::UPSTREAM_TIMEOUT_CODE
                );
            }

            return ServiceResponseDTO::fail(
                'No se pudo conectar con el servicio externo',
                503
            );
        } catch (RequestException $e) {
            return ServiceResponseDTO::fail(
                'Error al consultar servicio externo',
                502
            );
        } catch (Throwable $e) {
            return ServiceResponseDTO::fail(
                'Error inesperado en servicio externo',
                500
            );
        }
    }

    private function isTimeout(ConnectionException $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'timed out')
            || str_contains($message, 'timeout');
    }
}
