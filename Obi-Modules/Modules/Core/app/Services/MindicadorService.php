<?php

namespace Modules\Core\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class MindicadorService
{
    //URL base de la API de mindicador.cl
    private string $base;

    public function __construct()
    {
        $this->base    = config('services.mindicador.base_uri', 'https://mindicador.cl/api');
    }

    //UF del día de hoy (última publicación disponible).

    public function getCurrentUf(): float
    {
        $serie = $this->request('uf');

        return $serie[0]['valor'];
    }

     //UF para una fecha concreta (dia-mes-año).

    public function getUfByDate(\DateTimeInterface|string $date): ?float
    {
        // Normaliza la entrada a Carbon para manejarla cómodamente.
        $dt = $date instanceof \DateTimeInterface
              ? Carbon::instance($date)
              : Carbon::parse($date);

        // La API exige formato dd-mm-yyyy
        $ddmmyyyy = $dt->format('d-m-Y');

        $serie = $this->request("uf/{$ddmmyyyy}");

        return $serie[0]['valor'] ?? null; // null si la serie viene vacía
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
