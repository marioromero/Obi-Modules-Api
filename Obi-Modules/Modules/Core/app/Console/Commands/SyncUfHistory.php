<?php

namespace Modules\Core\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Core\app\Services\UfHistoryService;

class SyncUfHistory extends Command
{
    protected $signature = 'uf:sync
        {--months=5 : Meses a inicializar si el archivo no existe}
        {--from= : Fecha inicial Y-m-d para rellenar un rango}
        {--to= : Fecha final Y-m-d (por defecto hoy)}';

    protected $description = 'Inicializa o actualiza el archivo histórico diario de UF';

    public function handle(UfHistoryService $history): int
    {
        $to = $this->option('to');
        $from = $this->option('from');

        if ($from !== null) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = $to !== null ? Carbon::parse($to)->startOfDay() : now()->startOfDay();

            if ($fromDate->gt($toDate)) {
                $this->error('La fecha desde no puede ser mayor que la fecha hasta.');

                return self::FAILURE;
            }

            $written = $history->fillRange($fromDate, $toDate);
        } else {
            $written = $history->syncUntil($to);
        }

        $bounds = $history->bounds();

        $this->info("UF actualizadas: {$written}");
        $this->line('Archivo: '.$bounds['path']);
        $this->line('Desde: '.($bounds['first_date'] ?? 'sin datos'));
        $this->line('Hasta: '.($bounds['last_date'] ?? 'sin datos'));

        return self::SUCCESS;
    }
}
