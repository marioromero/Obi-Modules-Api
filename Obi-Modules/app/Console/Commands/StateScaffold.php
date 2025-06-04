<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class StateScaffold extends Command
{
    /**
     * Ejemplo:
     *   php artisan state:scaffold config/state-machines/cases.php
     */
    protected $signature   = 'state:scaffold {configFile : Ruta del archivo de configuración PHP}';
    protected $description = 'Genera máquina de estados, bitácora y actualiza modelo (sin Draft / Closed).';

    public function handle(): int
    {
        /* ----------------------------------------------------------------- */
        /* 1) Leer configuración                                              */
        /* ----------------------------------------------------------------- */
        $cfgPath = base_path($this->argument('configFile'));
        if (! File::exists($cfgPath)) {
            $this->error("Archivo no encontrado: {$cfgPath}");
            return self::FAILURE;
        }

        $cfg = require $cfgPath;
        foreach (['module','model','table','namespace','states','transitions','default'] as $k) {
            if (! isset($cfg[$k])) {
                $this->error("Falta la clave «{$k}» en la configuración.");
                return self::FAILURE;
            }
        }

        /* Parámetros clave */
        $module   = $cfg['module'];
        $model    = $cfg['model'];
        $table    = $cfg['table'];
        $ns       = $cfg['namespace'];
        $states   = $cfg['states'];
        $default  = $cfg['default'];

        $coreDir  = module_path($module, 'States/Core');
        $bizDir   = module_path($module, "States/{$ns}");
        $modelFQN = "Modules\\{$module}\\Models\\{$model}";

        /* ----------------------------------------------------------------- */
        /* 2) Clase abstracta base (sin Draft / Closed)                       */
        /* ----------------------------------------------------------------- */
        $baseFile = "{$coreDir}/{$model}State.php";
        if (! File::exists($baseFile)) {
            File::ensureDirectoryExists($coreDir, 0775, true);
            File::put($baseFile, $this->tplBase($module, $model));
            $this->info('✓ Clase abstracta de estado creada');
        }

        /* 🔑 IMPORTANTE: cargarla manualmente para que Composer la vea ahora */
        require_once $baseFile;

        /* ----------------------------------------------------------------- */
        /* 3) Migración para la columna `state`                              */
        /* ----------------------------------------------------------------- */
        $migName = "add_state_column_to_{$table}_table";
        $migFile = database_path('migrations/' . date('Y_m_d_His') . "_{$migName}.php");
        File::put($migFile, $this->tplAddStateMigration($table));
        $this->info("✓ Migración {$migName} creada");

        /* ----------------------------------------------------------------- */
        /* 4) Bitácora: migración + modelo                                    */
        /* ----------------------------------------------------------------- */
        $singular = Str::singular($table);          // cases → case
        $logTable = "{$singular}_step_logs";        // case_step_logs
        $parentFK = "{$singular}_id";               // case_id

        $migLog   = "create_{$logTable}_table";
        $migLogFile = database_path(
            'migrations/' . date('Y_m_d_His', time() + 1) . "_{$migLog}.php"
        );
        File::put($migLogFile, $this->tplLogMigration($logTable, $table, $parentFK));
        $this->info("✓ Migración {$migLog} creada");

        $logModelClass = "{$model}StepLog";
        $logModelPath  = module_path($module, "Models/{$logModelClass}.php");
        if (! File::exists($logModelPath)) {
            File::ensureDirectoryExists(dirname($logModelPath), 0775, true);
            File::put($logModelPath, $this->tplLogModel($module, $model, $logTable, $parentFK));
            $this->info("✓ Modelo {$logModelClass} creado");
        }

        /* ----------------------------------------------------------------- */
        /* 5) Actualizar el modelo padre                                      */
        /* ----------------------------------------------------------------- */
        if (! class_exists($modelFQN)) {
            $this->error("Modelo {$modelFQN} no encontrado (haz dump-autoload primero).");
            return self::FAILURE;
        }

        $modelFile = (new ReflectionClass($modelFQN))->getFileName();
        $code      = File::get($modelFile);

        /* imports */
        foreach ([
            'Spatie\ModelStates\HasStates',
            "Modules\\{$module}\\States\\Core\\{$model}State",
            "Modules\\{$module}\\Models\\{$logModelClass}",
        ] as $import) {
            if (! Str::contains($code, "use {$import};")) {
                $code = preg_replace(
                    '/^(<\?php\s+namespace\s+[^\n]+;\n(?:use .+\n)*)/',
                    "$1use {$import};\n",
                    $code,
                    1
                );
            }
        }

        /* trait HasStates */
        if (! Str::contains($code, 'use HasStates;')) {
            $code = preg_replace_callback(
                '/class\s+' . $model . '[^{]+\{/',
                fn($m) => $m[0] . "\n    use HasStates;",
                $code,
                1
            );
        }

        /* $casts['state'] */
        if (! Str::contains($code, "'state' =>")) {
            if (Str::contains($code, 'protected $casts')) {
                $code = preg_replace_callback(
                    '/protected\s+\$casts\s*=\s*\[([^\]]*)\]/s',
                    fn($m) =>
                        "protected \$casts = [\n        'state' => {$model}State::class," .
                        (trim($m[1]) ? "\n" . trim($m[1]) : '') . "\n    ]",
                    $code,
                    1
                );
            } else {
                $insert = "\n    protected \$casts = ['state' => {$model}State::class];\n";
                $code   = preg_replace('/\{/', '{' . $insert, $code, 1);
            }
        }

        /* relación stepLogs() */
        if (! Str::contains($code, 'function stepLogs(')) {
            $rel = <<<PHP

    public function stepLogs()
    {
        return \$this->hasMany({$logModelClass}::class);
    }
PHP;
            $code = preg_replace('/\}$/', $rel . '}', $code, 1);
        }

        File::put($modelFile, $code);
        $this->info('✓ Modelo padre actualizado');

        /* ----------------------------------------------------------------- */
        /* 6) Clases de estados de negocio                                   */
        /* ----------------------------------------------------------------- */
        File::ensureDirectoryExists($bizDir, 0775, true);
        foreach ($states as $state) {
            $stub = "{$bizDir}/{$state}.php";
            if (! File::exists($stub)) {
                File::put($stub, $this->tplBizState($module, $model, $ns, $state));
                $this->info("✓ Estado {$state} creado");
            }
        }

        /* ----------------------------------------------------------------- */
        /* 7) Archivo de transiciones (config)                               */
        /* ----------------------------------------------------------------- */
        $cfgOut = base_path("config/modules/{$module}/{$model}_states.php");
        File::ensureDirectoryExists(dirname($cfgOut), 0775, true);
        File::put($cfgOut, $this->tplStateConfig($module, $model, $ns, $states, $default, $cfg['transitions']));
        $this->info('✓ Archivo de transiciones generado');

        /* ----------------------------------------------------------------- */
        $this->line("\n💡  Ahora corre:");
        $this->comment('   composer dump-autoload');
        $this->comment('   php artisan migrate   # (con la conexión correcta)');

        return self::SUCCESS;
    }

    /* ============================================================= */
    /* =======================  Plantillas  ======================== */
    /* ============================================================= */

    private function tplBase(string $m, string $e): string
    {
        return <<<PHP
<?php

namespace Modules\\{$m}\\States\\Core;

use Spatie\\ModelStates\\State;
use Spatie\\ModelStates\\StateConfig;

abstract class {$e}State extends State
{
    abstract public static function label(): string;

    public static function config(): StateConfig
    {
        \$cfg = config('modules.{$m}.{$e}_states');

        \$sc = parent::config()->default(\$cfg['default']);

        foreach (\$cfg['transitions'] as \$from => \$tos) {
            foreach (\$tos as \$to) {
                \$sc->allowTransition(\$from, \$to);
            }
        }
        return \$sc;
    }
}
PHP;
    }

    private function tplAddStateMigration(string $table): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$t) {
            \$t->string('state', 50)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', fn(Blueprint \$t) => \$t->dropColumn('state'));
    }
};
PHP;
    }

    private function tplLogMigration(string $logTable, string $parentTable, string $parentFK): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$logTable}', function (Blueprint \$t) {
            \$t->id();
            \$t->foreignId('{$parentFK}')->constrained('{$parentTable}')->cascadeOnDelete();
            \$t->string('from_state', 120);
            \$t->string('to_state', 120);
            \$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            \$t->json('payload')->nullable();
            \$t->string('comment')->nullable();
            \$t->integer('duration_prev')->nullable();
            \$t->string('sub_status')->nullable();
            \$t->timestamp('created_at')->useCurrent();
            \$t->index('to_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$logTable}');
    }
};
PHP;
    }

    private function tplLogModel(string $m, string $e, string $table, string $fk): string
    {
        $class = $e . 'StepLog';

        return <<<PHP
<?php

namespace Modules\\{$m}\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$class} extends Model
{
    protected \$table = '{$table}';

    protected \$fillable = [
        '{$fk}',
        'from_state',
        'to_state',
        'user_id',
        'payload',
        'comment',
        'duration_prev',
        'sub_status',
    ];

    public \$timestamps = false;
}
PHP;
    }

    private function tplBizState(string $m, string $e, string $ns, string $state): string
    {
        return <<<PHP
<?php

namespace Modules\\{$m}\\States\\{$ns};

use Modules\\{$m}\\States\\Core\\{$e}State;

class {$state} extends {$e}State
{
    public static function label(): string
    {
        return '{$state}';
    }
}
PHP;
    }

private function tplStateConfig(
    string $m,
    string $e,
    string $ns,
    array  $states,
    string $default,
    array  $map
): string {
    /* use ...; */
    $uses = array_map(
        fn($s) => "use Modules\\{$m}\\States\\{$ns}\\{$s};",
        $states
    );
    $usesStr = implode("\n", $uses);

    /* transiciones */
    $lines = [];
    foreach ($map as $from => $tos) {
        $fromFQ = "Modules\\{$m}\\States\\{$ns}\\{$from}::class";
        $toFQ   = implode(', ', array_map(
            fn($t) => "Modules\\{$m}\\States\\{$ns}\\{$t}::class",
            $tos
        ));
        $lines[] = "        {$fromFQ} => [{$toFQ}],";
    }
    $transStr = implode("\n", $lines);

    return <<<PHP
<?php

{$usesStr}

return [
    'default' => Modules\\{$m}\\States\\{$ns}\\{$default}::class,

    'transitions' => [
{$transStr}
    ],
];
PHP;
}

}
