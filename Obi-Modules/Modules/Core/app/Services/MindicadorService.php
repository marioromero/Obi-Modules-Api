<?php

namespace Modules\Core\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\Core\app\Support\DTO\ServiceResponseDTO;

class MindicadorService
{
    //URL base de la API de mindicador.cl
    private string $base;

    public function __construct()
    {
        $this->base = config('services.mindicador.base_uri', 'https://mindicador.cl/api');
    }

    //UF del día de hoy (última publicación disponible).
    public function getCurrentUf(): ServiceResponseDTO
    {
        try {
            $serie = $this->request('uf');
            return ServiceResponseDTO::ok($serie[0]['valor'], 'UF obtenida correctamente');
        } catch (\Throwable $e) {
            return ServiceResponseDTO::fail('Fallo al obtener UF: ' . $e->getMessage(), 503);
        }
    }


    //UF para una fecha concreta (dia-mes-año).

    public function getUfByDate(\DateTimeInterface|string $date): ServiceResponseDTO
    {
        try {
            // Normaliza la entrada a Carbon
            $dt = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)
                : Carbon::parse($date);

            // Formato requerido por la API
            $ddmmyyyy = $dt->format('d-m-Y');

            $serie = $this->request("uf/{$ddmmyyyy}");

            $valor = $serie[0]['valor'] ?? null;

            if (is_null($valor)) {
                return ServiceResponseDTO::fail("No hay datos UF para la fecha {$ddmmyyyy}", 404);
            }

            return ServiceResponseDTO::ok($valor, "UF para el {$ddmmyyyy}");
        } catch (\Throwable $e) {
            return ServiceResponseDTO::fail("Error al consultar UF por fecha: " . $e->getMessage(), 503);
        }
    }

    //Método auxiliar privado
    //Hace la petición GET y devuelve solo la clave 'serie' del JSON
    private function request(string $path): array
    {
        return Http::acceptJson()
            ->get("{$this->base}/{$path}")
            ->throw()
            ->json('serie');
    }
}

