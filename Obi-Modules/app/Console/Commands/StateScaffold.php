<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class StateScaffold extends Command
{
    protected $signature = 'state:scaffold {configFile : Path al archivo de configuración PHP}';
    protected $description = 'Genera infraestructura + estados a partir de un archivo de configuración';

    /* --------------------------------------------------------------------- */
    public function handle(): int
    {
        /* 1) Cargar config */
        $cfgPath = base_path($this->argument('configFile'));
        if (!File::exists($cfgPath)) {
            $this->error("Archivo no encontrado: {$cfgPath}");
            return self::FAILURE;
        }

        $cfg = require $cfgPath;

        foreach (['module', 'model', 'table', 'namespace', 'states', 'transitions'] as $k) {
            if (!isset($cfg[$k])) {
                $this->error("Falta la clave «{$k}» en el archivo de configuración.");
                return self::FAILURE;
            }
        }

        /* 2) Rutas clave */
        $module = $cfg['module'];          // p.e. Cases
        $model = $cfg['model'];           // p.e. CaseEntity
        $table = $cfg['table'];           // p.e. cases
        $ns = $cfg['namespace'];       // p.e. Traro
        $coreDir = module_path($module, 'States/Core');
        $bizDir = module_path($module, "States/{$ns}");
        $modelFQN = "Modules\\{$module}\\Models\\{$model}";

        /* 3) Clase base + Draft/Closed si no existen */
        $baseFile = "{$coreDir}/{$model}State.php";
        if (!File::exists($baseFile)) {
            File::ensureDirectoryExists($coreDir);
            File::put($baseFile, $this->tplBase($module, $model));
            File::put("{$coreDir}/Draft.php", $this->tplCore($module, $model, 'Draft', 'Borrador'));
            File::put("{$coreDir}/Closed.php", $this->tplCore($module, $model, 'Closed', 'Cerrado'));
            $this->info('Clase base y estados Core creados');
        }

        /* 4) Migración add_state_column */
        $migName = 'add_state_column_to_' . $table . '_table';
        $migFile = database_path('migrations/' . date('Y_m_d_His') . "_{$migName}.php");
        File::put($migFile, $this->tplMigration($table));
        $this->info("Migración {$migName} creada");

        /* 5) Insertar trait + cast en el modelo */
        if (!class_exists($modelFQN)) {
            $this->error("Modelo {$modelFQN} no localizado (asegúrate de haberlo autoload-eado).");
            return self::FAILURE;
        }

        $modelFile = (new ReflectionClass($modelFQN))->getFileName();
        $code = File::get($modelFile);

// 5.1) Agregar "use Spatie\ModelStates\HasStates;" si no está
        if (!Str::contains($code, 'use Spatie\ModelStates\HasStates;')) {
            $code = preg_replace(
                '/^(<\?php\s+namespace\s+[^\n]+;\n)/',
                "$1use Spatie\\ModelStates\\HasStates;\n",
                $code,
                1
            );
        }

// 5.2) Agregar import del cast (CaseEntityState)
        $castClass = "{$model}State";
        $castFQN = "Modules\\{$module}\\States\\Core\\{$castClass}";

        if (!Str::contains($code, "use {$castFQN};")) {
            $code = preg_replace(
                '/^(<\?php\s+namespace\s+[^\n]+;\n(?:use .+\n)*)/',
                "$1use {$castFQN};\n",
                $code,
                1
            );
        }

// 5.3) Agregar trait "use HasStates;" dentro de la clase
        if (!Str::contains($code, 'use HasStates;')) {
            $code = preg_replace_callback('/class\s+' . $model . '\s+extends\s+[^\s]+(\s+implements[^{]+)?\s*\{/', function ($matches) {
                return $matches[0] . "\n    use HasStates;";
            }, $code, 1);
        }

// 5.4) Insertar o crear $casts
        if (Str::contains($code, 'protected $casts')) {
            if (!Str::contains($code, "'state' =>")) {
                $code = preg_replace_callback('/protected\s+\$casts\s*=\s*\[([^\]]*)\]/s', function ($matches) {
                    $existing = trim($matches[1]);
                    $newLine = "        'state' => CaseEntityState::class,";
                    return "protected \$casts = [\n" . $newLine . ($existing ? "\n" . $existing : '') . "\n    ]";
                }, $code, 1);
            }
        } else {
            // Insertar $casts completo después de la primera propiedad
            $insert = <<<PHP

    protected \$casts = [
        'state' => CaseEntityState::class,
    ];
PHP;

            $code = preg_replace('/(public|protected)\s+\$[\w]+\s*=[^;]+;/', '$0' . $insert, $code, 1);
        }

        File::put($modelFile, $code);
        $this->info('Modelo actualizado con trait, imports y cast limpios');

        /* 6) Stubs de estados de negocio */
        File::ensureDirectoryExists($bizDir);
        foreach ($cfg['states'] as $state) {
            $stubPath = "{$bizDir}/{$state}.php";
            if (!File::exists($stubPath)) {
                File::put($stubPath, $this->tplBiz($module, $model, $ns, $state));
                $this->info("Estado {$state} creado");
            }
        }

        /* 7) Config de transiciones */
        $cfgFile = base_path("config/modules/{$module}/{$model}_states.php");
        File::ensureDirectoryExists(dirname($cfgFile));
        File::put($cfgFile, $this->tplConfig($module, $model, $ns, $cfg));
        $this->info('Archivo de transiciones generado');

        $this->line("\nEjecuta:");
        $this->comment('  composer dump-autoload');
        $this->comment('  php artisan migrate');
        return self::SUCCESS;
    }

    /* ---------- plantillas simples ---------------------------------------------------- */

    private function tplBase(string $module, string $entity): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\States\\Core;

use Spatie\\ModelStates\\State;
use Spatie\\ModelStates\\StateConfig;

abstract class {$entity}State extends State
{
    abstract public static function label(): string;

    public static function config(): StateConfig
    {
        \$cfg = config('modules.{$module}.{$entity}_states', []);

        \$stateConfig = parent::config()->default(\$cfg['default'] ?? Draft::class);

        foreach ((\$cfg['transitions'] ?? []) as \$from => \$tos) {
            foreach (\$tos as \$to) {
                \$stateConfig->allowTransition(\$from, \$to);
            }
        }
        return \$stateConfig;
    }
}
PHP;
    }

    private function tplCore(string $module, string $entity, string $name, string $label): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\States\\Core;

class {$name} extends {$entity}State
{
    public static function label(): string
    {
        return '{$label}';
    }
}
PHP;
    }

    private function tplBiz(string $module, string $entity, string $ns, string $state): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\States\\{$ns};

use Modules\\{$module}\\States\\Core\\{$entity}State;

class {$state} extends {$entity}State
{
    public static function label(): string
    {
        return '{$state}';
    }
}
PHP;
    }

    private function tplMigration(string $table): string
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
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->string('state',50)->default('Draft')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn('state');
        });
    }
};
PHP;
    }

    private function tplConfig(string $module, string $entity, string $ns, array $cfg): string
    {
        /* líneas use */
        $uses = [
            "use Modules\\{$module}\\States\\Core\\Draft;",
            "use Modules\\{$module}\\States\\Core\\Closed;",
        ];
        foreach ($cfg['states'] as $s) {
            $uses[] = "use Modules\\{$module}\\States\\{$ns}\\{$s};";
        }
        $usesStr = implode("\n", $uses);          // ← salto real de línea

        /* mapa de transiciones */
        $lines = [];
        foreach ($cfg['transitions'] as $from => $tos) {
            $fromFq = $this->fqcn($module, $ns, $from);
            $toFq = array_map(fn($t) => $this->fqcn($module, $ns, $t), $tos);
            $lines[] = "        {$fromFq} => [" . implode(', ', $toFq) . "],";
        }
        $transitions = implode("\n", $lines);     // ← salto real de línea

        return <<<PHP
<?php

$usesStr

return [
    'default' => Draft::class,
    'transitions' => [
$transitions
    ],
];
PHP;
    }

    private function fqcn(string $module, string $ns, string $state): string
    {
        return in_array($state, ['Draft', 'Closed', 'Rejected'])
            ? "Modules\\{$module}\\States\\Core\\{$state}::class"
            : "Modules\\{$module}\\States\\{$ns}\\{$state}::class";
    }
}
