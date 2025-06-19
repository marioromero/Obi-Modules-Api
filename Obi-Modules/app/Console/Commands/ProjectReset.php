<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    protected $description = 'Ejecuta wipe, migrate, scaffold de estado, dump-autoload y optimize clear';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $commands = [
            'db:wipe-all' => [],
            'migrate:fresh' => [],
            'state:scaffold' => ['config/state-machines/cases.php'],
            'composer' => ['dump-autoload'],
            'optimize:clear' => [],
        ];

        foreach ($commands as $cmd => $params) {
            $this->info("🛠️ Ejecutando: $cmd " . implode(' ', $params));
            if ($cmd === 'composer') {
                // composer global
                $process = proc_open(
                    array_merge(['composer'], $params),
                    [
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w'],
                    ],
                    $pipes,
                    base_path(),
                    null
                );
                if (is_resource($process)) {
                    while (($line = fgets($pipes[1])) !== false) {
                        $this->line($line);
                    }
                    while (($err = fgets($pipes[2])) !== false) {
                        $this->error($err);
                    }
                    $exit = proc_close($process);
                } else {
                    $exit = 1;
                }
            } else {
                $exit = $this->call($cmd, array_combine(
                    array_map(fn($p) => $p, $params),
                    $params
                ));
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
