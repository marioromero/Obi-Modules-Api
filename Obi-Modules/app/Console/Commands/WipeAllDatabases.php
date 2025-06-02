<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WipeAllDatabases extends Command
{
    protected $signature = 'db:wipe-all';
    protected $description = 'Limpia todas las bases de datos del sistema OBI antes de migrar';

    public function handle(): void
    {
        $connections = [
            'users_db',
            'geography_db',
            'customers_db',
            'cases_db',
            'banks_db',
            'reports_db',
            'schedules_db',
            'mailing_db',
        ];

        foreach ($connections as $connection) {
            $this->info("🧹 Limpiando base de datos: $connection");
            Artisan::call('db:wipe', [
                '--database' => $connection,
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }

        $this->info("✅ Todas las bases de datos han sido limpiadas correctamente.");
    }
}
