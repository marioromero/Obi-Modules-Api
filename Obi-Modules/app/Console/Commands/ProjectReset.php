<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProjectReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta wipe, state:scaffold, composer dump-autoload, migrate y optimize:clear';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Orden corregido y parámetros con nombre para Artisan::call()
        $commands = [
            'db:wipe-all' => [],
            'state:scaffold' => ['configFile' => 'config/state-machines/cases.php'],
            'composer' => ['dump-autoload'],
            'migrate:fresh' => [],
            'optimize:clear' => [],
        ];

        foreach ($commands as $cmd => $params) {
            // Para el log, usamos array_values para no mostrar las claves en los comandos de Artisan
            $this->info("🛠️  Ejecutando: $cmd " . implode(' ', array_values($params)));

            $exit = 0;
            if ($cmd === 'composer') {
                $process = new Process(array_merge(['composer'], $params), base_path());
                $process->setTimeout(300); // Timeout de 5 minutos

                try {
                    // Usamos mustRun para que lance excepción si falla
                    $process->mustRun(function ($type, $buffer) {
                        $this->line(trim($buffer));
                    });
                } catch (ProcessFailedException $exception) {
                    $this->error($exception->getMessage());
                    $exit = $exception->getProcess()->getExitCode() ?? 1;
                }
            } else {
                // Llamada simplificada y correcta a los comandos de Artisan
                $exit = $this->call($cmd, $params);
            }

            if ($exit !== 0) {
                $this->error("❌ El comando '$cmd' devolvió código $exit. Abortando.");
                return $exit;
            }
        }

        $this->info('✅ Todos los comandos se ejecutaron correctamente.');
        return 0;
    }
}
