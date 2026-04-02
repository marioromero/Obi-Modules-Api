<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateCommuneDistances extends Command
{
    protected $signature = 'distances:calculate';

    protected $description = 'Calcula y guarda las distancias entre todas las comunas usando la fórmula de Haversine';

    // Definimos la conexión aquí para que sea fácil de cambiar si me equivoqué de base de datos
    protected string $dbConnection = 'geography_db';

    public function handle()
    {
        $this->info("Iniciando el cálculo de distancias en la conexión: {$this->dbConnection}...");

        // Usamos DB::connection()->table() en lugar de DB::table()
        $communes = DB::connection($this->dbConnection)->table('communes')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($communes->isEmpty()) {
            $this->error("No se encontraron comunas con coordenadas en la conexión {$this->dbConnection}.");
            return;
        }

        $dataToInsert = [];
        $totalCommunes = $communes->count();

        $bar = $this->output->createProgressBar($totalCommunes * $totalCommunes);
        $bar->start();

        foreach ($communes as $origin) {
            foreach ($communes as $destination) {
                if ($origin->id === $destination->id) {
                    $bar->advance();
                    continue;
                }

                $distance = $this->haversineGreatCircleDistance(
                    $origin->latitude, $origin->longitude,
                    $destination->latitude, $destination->longitude
                );

                $distanceKm = $distance * 1.13;

                $dataToInsert[] = [
                    'origin_id' => $origin->id,
                    'destination_id' => $destination->id,
                    'distance_km' => round($distanceKm, 2),
                ];

                if (count($dataToInsert) >= 5000) {
                    // Usamos la conexión explícita también para insertar
                    DB::connection($this->dbConnection)->table('commune_distances')->insertOrIgnore($dataToInsert);
                    $dataToInsert = [];
                }

                $bar->advance();
            }
        }

        if (!empty($dataToInsert)) {
            DB::connection($this->dbConnection)->table('commune_distances')->insertOrIgnore($dataToInsert);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('¡Todas las distancias fueron calculadas y guardadas con éxito!');
    }

    private function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
