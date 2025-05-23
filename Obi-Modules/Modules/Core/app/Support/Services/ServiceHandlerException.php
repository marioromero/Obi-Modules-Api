<?php
namespace Modules\Core\app\Support\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Modules\Core\App\Support\DTO\ServiceResponseDTO;
use Throwable;

class ServiceHandlerException
{
    public function fetchJson(string $url, string $jsonKey, string $successMessage): ServiceResponseDTO
    {
        try {
            $payload = Http::acceptJson()
                ->withOptions(['verify' => false])
                ->get($url)
                ->throw()
                ->json($jsonKey);

            return ServiceResponseDTO::ok($payload, $successMessage);
        } catch (ConnectionException $e) {
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
}
