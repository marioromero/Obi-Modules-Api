<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StateScaffold extends Command
{
    /**
     * Ejemplo de uso:
     *   php artisan state:scaffold config/state-machines/cases.php
     */
    protected $signature = 'state:scaffold {configFile : Ruta del archivo de configuración PHP}';
    protected $description = 'Genera la máquina de estados, bitácora y actualiza modelo según config.';

    public function handle(): int
    {

        //────────────────────────────────────────────────────────────────────
        // BLOQUE 1: Lectura y validación del archivo de configuración
        //────────────────────────────────────────────────────────────────────

        // 1) Leer y validar existencia del archivo
        $configPath = base_path($this->argument('configFile'));
        if (!File::exists($configPath)) {
            $this->error("❌ Archivo de configuración no encontrado: {$configPath}");
            return self::FAILURE;
        }

        // 2) Cargar el array de configuración
        $cfg = require $configPath;

        // 3) Validar claves obligatorias
        $required = [
            'connection', 'module', 'model', 'table', 'namespace',
            'default', 'states', 'transitions', 'sub_states',
            'closing_steps', 'auto_transitions', 'overall_status',
        ];
        foreach ($required as $key) {
            if (!array_key_exists($key, $cfg)) {
                $this->error("❌ Falta la clave «{$key}» en la configuración.");
                return self::FAILURE;
            }
        }

        // 4) Confirmación de éxito de esta etapa
        $this->info("✅ Configuración cargada y validada correctamente.");

        //────────────────────────────────────────────────────────────────────
        // AQUÍ CONTINUARÍA BLOQUE 2: Estructuras de directorio y rutas…
        // … (usa $cfg['module'], $cfg['model'], $cfg['table'], $cfg['namespace'], etc.)
        //────────────────────────────────────────────────────────────────────

        // Extraer datos del config
        $module = $cfg['module'];
        $model = $cfg['model'];
        $table = $cfg['table'];
        $ns = $cfg['namespace'];

        // Rutas principales
        $coreDir = module_path($module, 'States/Core');
        $bizDir = module_path($module, "States/{$ns}");
        $modelsDir = module_path($module, 'Models');
        $configDir = config_path("modules/{$module}");

        // Nombres auxiliares
        $singular = Str::snake(Str::singular($table));
        $logTable = "{$singular}_step_logs";

        // Crear carpetas si no existen
        File::ensureDirectoryExists($coreDir, 0775, true);
        $this->info("✅ Directorio de estados Core creado: {$coreDir}");

        File::ensureDirectoryExists($bizDir, 0775, true);
        $this->info("✅ Directorio de estados de negocio creado: {$bizDir}");

        File::ensureDirectoryExists($modelsDir, 0775, true);
        $this->info("✅ Directorio de modelos creado: {$modelsDir}");

        File::ensureDirectoryExists($configDir, 0775, true);
        $this->info("✅ Directorio de config/modules creado: {$configDir}");

        //────────────────────────────────────────────────────────────────────
        // BLOQUE 3: Generación de la clase abstracta base de estado
        //────────────────────────────────────────────────────────────────────

        $module = $cfg['module'];
        $model = $cfg['model'];
        $coreDir = module_path($module, 'States/Core');
        $baseFile = "{$coreDir}/{$model}State.php";

        if (!File::exists($baseFile)) {
            // Usamos la plantilla tplBase para crear la clase
            File::put($baseFile, $this->tplBase($module, $model));
            $this->info("✓ Clase abstracta de estado creada: {$baseFile}");
        }

        // Forzamos la inclusión inmediata para que Composer la reconozca
        require_once $baseFile;

//────────────────────────────────────────────────────────────────────
// BLOQUE 4: Generación de migraciones dentro del módulo
//────────────────────────────────────────────────────────────────────

        $connection = $cfg['connection'] ?? config('database.default');
        $table = $cfg['table'];
        $module = $cfg['module'];
        $singular = Str::snake(Str::singular($table));
        $logTable = "{$singular}_step_logs";
        $parentFK = "{$singular}_id";

// 4.0) Asegurar que exista el directorio de migraciones del módulo
        $migDir = module_path($module, 'database/migrations');
        File::ensureDirectoryExists($migDir, 0775, true);

//────────────────────────────────────────────────────────────────────
// 4.1) Migración: añadir columna `state` a la tabla principal
//────────────────────────────────────────────────────────────────────
        $migName = "add_state_column_to_{$table}_table";
        $migFile = "{$migDir}/" . date('Y_m_d_His') . "_{$migName}.php";
        File::put(
            $migFile,
            $this->tplAddStateMigration($connection, $table, $cfg['default'])
        );
        $this->info("✓ Migración {$migName} creada en módulo: {$migFile}");

//────────────────────────────────────────────────────────────────────
// 4.2) Migración: crear tabla de logs de estado y sub-estado
//────────────────────────────────────────────────────────────────────
        $migLogName = "create_{$logTable}_table";
        $migLogFile = "{$migDir}/" . date('Y_m_d_His', time() + 1) . "_{$migLogName}.php";
        File::put(
            $migLogFile,
            $this->tplLogMigration($connection, $logTable, $table, $parentFK)
        );
        $this->info("✓ Migración {$migLogName} creada en módulo: {$migLogFile}");

//────────────────────────────────────────────────────────────────────
// 4.3) Migración: añadir columnas de sub-estados y overall_status
//────────────────────────────────────────────────────────────────────
        $migSubName = "add_substates_overall_to_{$table}_table";
        $migSubFile = "{$migDir}/" . date('Y_m_d_His', time() + 2) . "_{$migSubName}.php";
        File::put(
            $migSubFile,
            $this->tplAddSubStatesMigration(
                $connection,
                $table,
                $cfg['sub_states'],
                $cfg['overall_status']
            )
        );
        $this->info("✓ Migración {$migSubName} creada en módulo: {$migSubFile}");

//────────────────────────────────────────────────────────────────────
// BLOQUE 5: Actualizar modelo padre con estados y relación a logs
//────────────────────────────────────────────────────────────────────
        $logModelClass = "{$model}StepLog";
        $this->updateParentModel($cfg, $logModelClass);

        //────────────────────────────────────────────────────────────────────
// BLOQUE 6: Generar clases concretas de estado (negocio)
//────────────────────────────────────────────────────────────────────

        $bizDir = module_path($module, "States/{$ns}");
        $model = $cfg['model'];

// Asegurar directorio de estados de negocio existe
        File::ensureDirectoryExists($bizDir, 0775, true);

// Iterar y generar cada estado si no existe
        foreach ($cfg['states'] as $state) {
            $stateFile = "{$bizDir}/{$state}.php";

            if (!File::exists($stateFile)) {
                File::put($stateFile, $this->tplBizState($module, $model, $ns, $state));
                $this->info("✓ Clase de estado creada: {$stateFile}");
            } else {
                $this->info("⚠️  Clase de estado ya existía (omitida): {$stateFile}");
            }
        }

//────────────────────────────────────────────────────────────────────
// BLOQUE 6.1: Generar archivo de configuración de transiciones
//────────────────────────────────────────────────────────────────────

        $configOutputPath = config_path("modules/{$module}/{$model}_states.php");
        File::ensureDirectoryExists(dirname($configOutputPath), 0775, true);

// Generar archivo de configuración de transiciones
        File::put(
            $configOutputPath,
            $this->tplStateConfig(
                $module,
                $model,
                $ns,
                $cfg['states'],
                $cfg['default'],
                $cfg['transitions'],
                $cfg['sub_states'],
                $cfg['closing_steps'],
                $cfg['auto_transitions'],
                $cfg['overall_status']
            )
        );
        $this->info("✓ Configuración de transiciones generada: {$configOutputPath}");

        //────────────────────────────────────────────────────────────────────
// BLOQUE 7: Generar archivo config de módulo y configurar provider
//────────────────────────────────────────────────────────────────────

        $configModuleDir = module_path($module, 'Config');
        File::ensureDirectoryExists($configModuleDir, 0775, true);

// Archivo de configuración de estados dentro del módulo
        $moduleConfigFile = "{$configModuleDir}/{$model}_states.php";

// Copiamos el archivo generado anteriormente en la config general al módulo
        $configOutputPath = config_path("modules/{$module}/{$model}_states.php");
        if (File::exists($configOutputPath)) {
            File::copy($configOutputPath, $moduleConfigFile);
            $this->info("✓ Archivo de configuración copiado al módulo: {$moduleConfigFile}");
        } else {
            $this->error("❌ Archivo de configuración origen no encontrado: {$configOutputPath}");
            return self::FAILURE;
        }

//────────────────────────────────────────────────────────────────────
// BLOQUE 7.1: Generar o actualizar el ConfigServiceProvider del módulo
//────────────────────────────────────────────────────────────────────

        $providersDir = module_path($module, 'Providers');
        File::ensureDirectoryExists($providersDir, 0775, true);

        $configProviderFile = "{$providersDir}/ConfigServiceProvider.php";

// Si no existe, lo creamos; si existe, verificamos que contenga la línea necesaria
        if (!File::exists($configProviderFile)) {
            File::put(
                $configProviderFile,
                $this->tplConfigServiceProvider($module, $model)
            );
            $this->info("✓ ConfigServiceProvider creado: {$configProviderFile}");
        } else {
            $providerContent = File::get($configProviderFile);
            $mergeLine = "\$this->mergeConfigFrom(module_path('{$module}', 'Config/{$model}_states.php'), 'modules.{$module}.{$model}_states');";

            if (!Str::contains($providerContent, $mergeLine)) {
                // Insertamos la línea antes del cierre del método register()
                $providerContent = preg_replace(
                    '/(public function register\(\): void\s*{\s*)/s',
                    "$1\n        {$mergeLine}\n",
                    $providerContent,
                    1
                );

                File::put($configProviderFile, $providerContent);
                $this->info("✓ ConfigServiceProvider actualizado con nueva configuración: {$configProviderFile}");
            } else {
                $this->info("⚠️  ConfigServiceProvider ya contiene la configuración (sin cambios): {$configProviderFile}");
            }
        }

//────────────────────────────────────────────────────────────────────
// BLOQUE 7.2: Asegurar que ConfigServiceProvider esté listado en module.json
//────────────────────────────────────────────────────────────────────

        $moduleJsonPath = module_path($module, 'module.json');
        if (File::exists($moduleJsonPath)) {
            $moduleJson = json_decode(File::get($moduleJsonPath), true);

            $configProviderClass = "Modules\\{$module}\\Providers\\ConfigServiceProvider";
            if (!in_array($configProviderClass, $moduleJson['providers'] ?? [])) {
                $moduleJson['providers'][] = $configProviderClass;
                File::put($moduleJsonPath, json_encode($moduleJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info("✓ ConfigServiceProvider registrado en module.json: {$configProviderClass}");
            } else {
                $this->info("⚠️  ConfigServiceProvider ya registrado en module.json (sin cambios).");
            }
        } else {
            $this->error("❌ Archivo module.json no encontrado en módulo: {$moduleJsonPath}");
            return self::FAILURE;
        }

        //────────────────────────────────────────────────────────────────────
// BLOQUE 8: Modelo, Listener de estado y Observer de sub-estados
//────────────────────────────────────────────────────────────────────

        /**
         * Parámetros que ya tienes:
         *   $module, $model, $table, $singular, $logTable, $parentFK, $connection
         */

// 8.1) Asegurarnos de generar el modelo de logs con su conexión
        $logModelClass = "{$model}StepLog";
        $logModelPath = module_path($module, "Models/{$logModelClass}.php");

        if (!File::exists($logModelPath)) {
            File::ensureDirectoryExists(dirname($logModelPath), 0775, true);
            File::put(
                $logModelPath,
                $this->tplLogModel(
                    $module,
                    $logModelClass,
                    $logTable,
                    $parentFK,
                    $cfg['connection']              // <-- pasamos la conexión aquí
                )
            );
            $this->info("✓ Modelo {$logModelClass} creado con conexión {$cfg['connection']}");
        } else {
            $this->info("⚠️  Modelo {$logModelClass} ya existe (omitido)");
        }

// 8.2) Generar (o reimprimir) el Listener de cambios de estado
        $listenerDir = module_path($module, 'Listeners');
        $listenerFile = "{$listenerDir}/Log{$model}StateTransition.php";

        File::ensureDirectoryExists($listenerDir, 0775, true);

        File::put(
            $listenerFile,
            $this->tplStateListener(
                $module,
                $model,
                $table,
                $parentFK,
                $cfg['sub_states']
            )
        );
        $this->info("✓ Listener Log{$model}StateTransition creado/actualizado");

// 8.3) Registrar el Listener en EventServiceProvider (reemplaza el bloque $listen vacío)
        $eventProviderFile = module_path($module, 'Providers/EventServiceProvider.php');
        $eventProviderCode = File::get($eventProviderFile);

        $useEvent = 'use Spatie\\ModelStates\\Events\\StateChanged;';
        $useListener = "use Modules\\{$module}\\Listeners\\Log{$model}StateTransition;";

        if (!Str::contains($eventProviderCode, $useEvent)) {
            $eventProviderCode = preg_replace(
                '/(^<\?php\s+namespace\s+[^\n]+;\n)/m',
                "$1{$useEvent}\n{$useListener}\n",
                $eventProviderCode,
                1
            );
        }

        $listenMapping = <<<PHP
    protected \$listen = [
        StateChanged::class => [
            Log{$model}StateTransition::class,
        ],
    ];
PHP;

        $eventProviderCode = preg_replace(
            '/protected\s+\$listen\s*=\s*\[\s*\];/m',
            $listenMapping,
            $eventProviderCode,
            1
        );

        File::put($eventProviderFile, $eventProviderCode);
        $this->info("✓ EventServiceProvider actualizado con el listener");

//────────────────────────────────────────────────────────────────────
// 8.4) Generar Observer para cambios de sub-estado
//────────────────────────────────────────────────────────────────────
        $obsDir = module_path($module, 'Observers');
        $obsFile = "{$obsDir}/{$model}SubstateObserver.php";

        File::ensureDirectoryExists($obsDir, 0775, true);

        File::put(
            $obsFile,
            $this->tplSubstateObserver(
                $module,
                $model,
                $parentFK,
                $cfg['sub_states']
            )
        );
        $this->info("✓ Observer {$model}SubstateObserver creado");

// 8.5) Registrar el Observer en el ServiceProvider del módulo
        $moduleServiceProvider = "{$module}ServiceProvider";
        $svcProvFile = module_path($module, "Providers/{$moduleServiceProvider}.php");
        $svcProvCode = File::get($svcProvFile);

        $modelUse = "use Modules\\{$module}\\Models\\{$model};";
        $observerUse = "use Modules\\{$module}\\Observers\\{$model}SubstateObserver;";
        $observeLine = "{$model}::observe({$model}SubstateObserver::class);";

        if (!Str::contains($svcProvCode, $observerUse)) {
            $svcProvCode = preg_replace(
                '/(^<\?php\s+namespace\s+[^\n]+;\n)/m',
                "$1{$observerUse}\n",
                $svcProvCode,
                1
            );
        }
        if (!Str::contains($svcProvCode, $modelUse)) {
            $svcProvCode = preg_replace(
                '/(^<\?php\s+namespace\s+[^\n]+;\n)/m',
                "$1{$modelUse}\n",
                $svcProvCode,
                1
            );
        }
        if (!Str::contains($svcProvCode, $observeLine)) {
            $svcProvCode = preg_replace(
                '/public function boot\(\): void\s*\{/m',
                "public function boot(): void\n    {\n        {$observeLine}\n",
                $svcProvCode,
                1
            );
        }

        File::put($svcProvFile, $svcProvCode);
        $this->info("✓ Observer registrado en {$moduleServiceProvider}");

        return self::SUCCESS;
    }


    /**
     * tplBase
     *
     * Crea la clase abstracta de estado con fallback de config,
     * validación, registro de transiciones y helper isAny/is.
     */
    private function tplBase(string $module, string $model): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\States\\Core;

use Spatie\\ModelStates\\State;
use Spatie\\ModelStates\\StateConfig;
use Illuminate\\Support\\Arr;

abstract class {$model}State extends State
{
    abstract public static function label(): string;

    public static function config(): StateConfig
    {
        // 1) Intentar cargar config desde Laravel
        \$cfg = config('modules.{$module}.{$model}_states');

        // 2) Fallback a archivo si aún no está mergeado
        if (! is_array(\$cfg)) {
            \$path = module_path('{$module}', 'Config/{$model}_states.php');
            if (! file_exists(\$path)) {
                throw new \\RuntimeException("No se encontró Config/{$model}_states.php");
            }
            \$cfg = require \$path;
        }

        // 3) Validar estructura mínima
        if (! isset(\$cfg['default'], \$cfg['transitions']) || ! is_array(\$cfg['transitions'])) {
            throw new \\InvalidArgumentException(
                "El config {$model}_states debe tener 'default' y 'transitions' como array"
            );
        }

        // 4) Crear StateConfig con default
        /** @var StateConfig \$sc */
        \$sc = parent::config()->default(\$cfg['default']);

        // 5) Registrar transiciones
        foreach (\$cfg['transitions'] as \$from => \$tos) {
            if (! is_array(\$tos)) continue;
            foreach (\$tos as \$to) {
                \$sc->allowTransition(\$from, \$to);
            }
        }

        // 6) Registrar todos los estados para evitar instanciaciones inválidas
        \$allStates = array_unique(array_merge(
            array_keys(\$cfg['transitions']),
            Arr::flatten(array_values(\$cfg['transitions']))
        ));
        \$sc->registerState(\$allStates);

        return \$sc;
    }

    /**
     * Alias a equals(), chequea múltiples posibles clases.
     */
    public function isAny(string ...\$stateClasses): bool
    {
        foreach (\$stateClasses as \$st) {
            if (\$this->equals(\$st)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Alias a equals(), para soporte legacy de is().
     */
    public function is(string \$stateClass): bool
    {
        return \$this->equals(\$stateClass);
    }
}
PHP;
    }

    /**
     * tplAddStateMigration
     * Migración para añadir la columna principal "state"
     */
    private function tplAddStateMigration(string $connection, string $table, string $defaultState): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    protected \$connection = '{$connection}';

    public function up(): void
    {
        Schema::connection('{$connection}')->table('{$table}', function (Blueprint \$t) {
            \$t->string('state', 120)->default('{$defaultState}')->after('id');
        });
    }

    public function down(): void
    {
        Schema::connection('{$connection}')->table('{$table}', function (Blueprint \$t) {
            \$t->dropColumn('state');
        });
    }
};
PHP;
    }

    /**
     * tplLogMigration
     * Crea la tabla para registrar logs de estados y subestados
     */
    private function tplLogMigration(string $connection, string $logTable, string $parentTable, string $parentFK): string
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    protected \$connection = '{$connection}';

    public function up(): void
    {
        Schema::connection('{$connection}')->create('{$logTable}', function (Blueprint \$t) {
            \$t->id();
            \$t->foreignId('{$parentFK}')->constrained('{$parentTable}')->cascadeOnDelete();
            \$t->string('from_state', 120)->nullable();
            \$t->string('to_state', 120);
            \$t->string('from_sub', 120)->nullable();
            \$t->string('to_sub', 120)->nullable();
            \$t->enum('type', ['state', 'sub_state'])->default('state');
            \$t->unsignedBigInteger('user_id')->nullable();
            \$t->json('payload')->nullable();
            \$t->longText('comments')->nullable();
            \$t->timestamp('created_at')->useCurrent();
            \$t->index('{$parentFK}');
            \$t->index('to_state');
        });
    }

    public function down(): void
    {
        Schema::connection('{$connection}')->dropIfExists('{$logTable}');
    }
};
PHP;
    }

    /**
     * tplLogModel
     * Modelo Eloquent asociado a la tabla de logs generada.
     */
    private function tplLogModel(string $module, string $logModelClass, string $logTable, string $parentFK, string $connection): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$logModelClass} extends Model
{
    protected \$connection = '{$connection}';
    protected \$table      = '{$logTable}';

    protected \$fillable = [
        '{$parentFK}',
        'from_state', 'to_state',
        'from_sub',   'to_sub',
        'type',
        'user_id',
        'payload',
        'comments',
        'created_at',
    ];

    public \$timestamps = false;
}
PHP;
    }

    /**
     * tplAddSubStatesMigration
     *
     * – La primera columna de sub-estado NO es nullable y lleva DEFAULT al valor inicial.
     * – Las demás columnas de sub-estado son nullable.
     * – La columna overall_status NO es nullable y lleva DEFAULT al valor por defecto.
     */
    private function tplAddSubStatesMigration(string $connection, string $table, array $subStates, array $overall): string
    {
        $upLines = [];
        $downCols = [];
        $first = true;

        foreach ($subStates as $state => $info) {
            $col = $info['column'];
            $vals = implode("','", $info['values']);

            if ($first) {
                // primera columna no-nullable con default inicial
                $def = $info['default'];
                $upLines[] = "\$t->enum('{$col}', ['{$vals}'])->default('{$def}');";
                $first = false;
            } else {
                $upLines[] = "\$t->enum('{$col}', ['{$vals}'])->nullable();";
            }

            $downCols[] = "'{$col}'";
        }

        // overall_status: no-nullable con default del config
        $ovCol = $overall['column'];
        $ovVals = implode("','", $overall['values']);
        $ovDef = $overall['default'];
        $upLines[] = "\$t->enum('{$ovCol}', ['{$ovVals}'])->default('{$ovDef}');";
        $downCols[] = "'{$ovCol}'";

        $upBody = implode("\n            ", $upLines);
        $downBody = implode(', ', $downCols);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected \$connection = '{$connection}';

    public function up(): void
    {
        Schema::connection('{$connection}')->table('{$table}', function (Blueprint \$t) {
            {$upBody}
        });
    }

    public function down(): void
    {
        Schema::connection('{$connection}')->table('{$table}', function (Blueprint \$t) {
            \$t->dropColumn([{$downBody}]);
        });
    }
};
PHP;
    }

//──────────────────────────────────────────────────────────
// 3) updateParentModel
//──────────────────────────────────────────────────────────
    private function updateParentModel(array $cfg, string $logModelClass): void
    {
        $singular = Str::snake(Str::singular($cfg['table']));
        $parentFK = "{$singular}_id";
        $modelFQN = "Modules\\{$cfg['module']}\\Models\\{$cfg['model']}";

        if (!class_exists($modelFQN)) {
            $this->error("❌ Modelo {$modelFQN} no encontrado. Ejecuta primero composer dump-autoload.");
            return;
        }

        $modelFile = (new \ReflectionClass($modelFQN))->getFileName();
        $code = File::get($modelFile);

        // 1) Imports necesarios (incluyendo DB)
        $imports = [
            'Illuminate\Support\Facades\DB',
            'Spatie\ModelStates\HasStates',
            "Modules\\{$cfg['module']}\\States\\Core\\{$cfg['model']}State",
            "Modules\\{$cfg['module']}\\Models\\{$logModelClass}",
        ];
        foreach ($imports as $import) {
            if (!Str::contains($code, "use {$import};")) {
                $code = preg_replace(
                    '/^(<\?php\s+namespace\s+[^\n]+;\n(?:use .+\n)*)/',
                    "$1use {$import};\n",
                    $code,
                    1
                );
            }
        }

        // 2) Trait HasStates
        if (!Str::contains($code, 'use HasStates;')) {
            $code = preg_replace_callback(
                '/class\s+' . preg_quote($cfg['model'], '/') . '[^{]*\{/m',
                fn($m) => $m[0] . "\n    use HasStates;\n",
                $code,
                1
            );
        }

        // 3) Casts columna state
        if (!Str::contains($code, "'state' =>")) {
            if (Str::contains($code, 'protected $casts')) {
                $code = preg_replace_callback(
                    '/protected\s+\$casts\s*=\s*\[([^\]]*)\]/s',
                    fn($m) => "protected \$casts = [\n        'state' => {$cfg['model']}State::class," .
                        (trim($m[1]) ? "\n" . trim($m[1]) : '') .
                        "\n    ]",
                    $code,
                    1
                );
            } else {
                $insert = "\n    protected \$casts = [\n        'state' => {$cfg['model']}State::class\n    ];\n";
                $code = preg_replace('/\{/', '{' . $insert, $code, 1);
            }
        }

// 4) Relación stepLogs
        if (!Str::contains($code, 'function stepLogs(')) {
            $rel = <<<PHP

    public function stepLogs()
    {
        return \$this->hasMany({$logModelClass}::class, '{$parentFK}');
    }

PHP;
            $code = preg_replace('/\}\s*$/', $rel . "\n}", $code, 1);
        }

// 5) Injector transitionSubstate (pasamos también parentFK y namespace)
        if (!Str::contains($code, 'function transitionSubstate')) {
            $stub = $this->tplTransitionSubstate(
                $cfg['module'],
                $cfg['model'],
                $parentFK,           // ej. "case_id"
                $cfg['namespace']    // ej. "Traro"
            );
            $code = preg_replace('/\}\s*$/', $stub . "\n}", $code, 1);
        }

        // 6) Injector transitionToWithComments
        if (!Str::contains($code, 'function transitionToWithComments')) {
            $stub = $this->tplTransitionToWithComments($cfg['module'], $cfg['model']);
            $code = preg_replace('/\}\s*$/', $stub . "\n}", $code, 1);
        }

        // Guardar cambios en el modelo
        File::put($modelFile, $code);
        $this->info("✓ Modelo {$cfg['model']} actualizado con transitionSubstate y demás.");
    }

    /**
     * Plantilla para estado concreto de negocio.
     */
    private function tplBizState(string $module, string $model, string $ns, string $state): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\States\\{$ns};

use Modules\\{$module}\\States\\Core\\{$model}State;

class {$state} extends {$model}State
{
    public static function label(): string
    {
        return '{$state}';
    }
}
PHP;
    }

    //──────────────────────────────────────────────────────────
// 1) tplStateConfig
//──────────────────────────────────────────────────────────

    private function tplStateConfig(
        string $module,
        string $model,
        string $ns,
        array  $states,
        string $default,
        array  $transitions,
        array  $subStatesConfig,
        array  $closingSteps,
        array  $autoTransitions,
        array  $overallStatus
    ): string
    {
        // Imports de clases de estados
        $imports = implode("\n", array_map(
            fn($state) => "use Modules\\{$module}\\States\\{$ns}\\{$state};",
            $states
        ));

        // Exportar array 'states' para preservar orden
        $statesExport = var_export($states, true);

        // Construir bloque de transiciones con ::class
        $transitionsStr = '';
        foreach ($transitions as $from => $tos) {
            $fromCls = "{$from}::class";
            $tosCls = implode(', ', array_map(fn($to) => "{$to}::class", $tos));
            $transitionsStr .= "        {$fromCls} => [{$tosCls}],\n";
        }

        // Exportar sub_states, closing_steps, auto_transitions, overall_status
        $subStatesExport = var_export($subStatesConfig, true);
        $closingExport = var_export(array_values($closingSteps), true);
        $autoTransExport = var_export($autoTransitions, true);
        $overallExport = var_export($overallStatus, true);

        return <<<PHP
<?php

{$imports}

return [
    // Lista de estados globales, en orden
    'states'           => {$statesExport},

    // Estado inicial global
    'default'          => {$default}::class,

    // Transiciones permitidas
    'transitions'      => [
{$transitionsStr}    ],

    // Sub-estados por cada estado global
    'sub_states'       => {$subStatesExport},

    // Estados terminales
    'closing_steps'    => {$closingExport},

    // Transición automática de estado global
    'auto_transitions' => {$autoTransExport},

    // Configuración de overall_status y sus triggers
    'overall_status'   => {$overallExport},
];
PHP;
    }


    /**
     * Plantilla para ConfigServiceProvider del módulo.
     */
    private function tplConfigServiceProvider(string $module, string $model): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        \$this->mergeConfigFrom(
            module_path('{$module}', 'Config/{$model}_states.php'),
            'modules.{$module}.{$model}_states'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
PHP;
    }

    /**
     * tplStateListener
     *
     * – Al avanzar a estados no terminales: cierra el sub-estado anterior con su ‘final’.
     * – Al avanzar a un estado terminal: mantiene el sub-estado anterior tal cual.
     * – Al retroceder: captura el sub-estado real y anula la columna anterior.
     * – Si llega a un estado terminal (closing_steps): pone overall_status = 'cerrado'.
     * – Si des-cierra (fromState en closing_steps y toState no): pone overall_status = 'con pendientes'.
     */
    private function tplStateListener(
        string $module,
        string $model,
        string $table,
        string $parentFK,
        array  $subStatesConfig
    ): string
    {
        $subStates = var_export($subStatesConfig, true);
        $configKey = "modules.{$module}.{$model}_states";
        $logModelName = "{$model}StepLog";

        return <<<PHP
<?php

namespace Modules\\{$module}\\Listeners;

use Spatie\\ModelStates\\Events\\StateChanged;
use Modules\\{$module}\\Models\\{$model};
use Modules\\{$module}\\Models\\{$logModelName};

class Log{$model}StateTransition
{
    private static \$subStates = {$subStates};

    public function handle(StateChanged \$event): void
    {
        if (! \$event->model instanceof {$model}) {
            return;
        }

        // 1) Preparar entidad y recargar datos
        \$entity    = \$event->model;
        \$entity->refresh();

        // 2) Estados globales
        \$fromState = \$event->initialState
            ? class_basename(\$event->initialState)
            : null;
        \$toState   = class_basename(\$event->finalState);

        // 3) Cargar configuración
        \$cfg           = config('{$configKey}');
        \$statesList    = \$cfg['states'];
        \$closingSteps  = \$cfg['closing_steps'];
        \$overallCol    = \$cfg['overall_status']['column'];

        // 4) Índices avance/retroceso
        \$fromIdx = \$fromState
            ? array_search(\$fromState, \$statesList, true)
            : false;
        \$toIdx   = array_search(\$toState,   \$statesList, true);

        // 5) Capturar y actualizar sub-estado anterior
        \$fromSubLogged = null;
        if (\$fromState && isset(self::\$subStates[\$fromState])) {
            \$oldCol = self::\$subStates[\$fromState]['column'];

            if (\$toIdx > \$fromIdx && ! in_array(\$toState, \$closingSteps, true)) {
                // → Avance normal (no terminal): forzar 'final'
                \$fromSubLogged      = self::\$subStates[\$fromState]['final'];
                \$entity->{\$oldCol} = \$fromSubLogged;
            } elseif (\$toIdx > \$fromIdx && in_array(\$toState, \$closingSteps, true)) {
                // → Avance a terminal: capturar real, no modificar
                \$fromSubLogged = \$entity->{\$oldCol};
            } else {
                // ← Retroceso: capturar real y anular
                \$fromSubLogged      = \$entity->{\$oldCol};
                \$entity->{\$oldCol} = null;
            }

            \$entity->saveQuietly();
        }

        // 6) Abrir sub-estado del nuevo estado
        \$toSub = null;
        if (isset(self::\$subStates[\$toState])) {
            \$newCol     = self::\$subStates[\$toState]['column'];
            \$newDefault = self::\$subStates[\$toState]['default'];
            \$entity->{\$newCol} = \$newDefault;
            \$entity->saveQuietly();
            \$toSub = \$newDefault;
        }

        // 7) Cierre global
        if (in_array(\$toState, \$closingSteps, true)) {
            \$entity->{\$overallCol} = 'cerrado';
            \$entity->saveQuietly();
        }

        // 8) Des-cierre
        if (in_array(\$fromState, \$closingSteps, true)
            && ! in_array(\$toState, \$closingSteps, true)
        ) {
            \$lastLog = {$logModelName}::query()
                ->where('{$parentFK}', \$entity->id)
                ->whereNotIn('to_state', \$closingSteps)
                ->where('type', 'state')
                ->orderBy('created_at','desc')
                ->first();

            if (\$lastLog && isset(self::\$subStates[\$lastLog->to_state])) {
                \$col = self::\$subStates[\$lastLog->to_state]['column'];
                \$entity->{\$col} = self::\$subStates[\$lastLog->to_state]['default'];
            }

            \$entity->{\$overallCol} = 'con pendientes';
            \$entity->saveQuietly();
        }

        // 9) Transiciones normales: verificar si el nuevo subestado debe marcar como pendiente
        if (!in_array(\$toState, \$closingSteps, true)
            && !in_array(\$fromState, \$closingSteps, true)) {

            \$pendingSubStates = \$cfg['overall_status']['triggers']['pending']['sub_states'] ?? [];

            if (\$toSub && in_array(\$toSub, \$pendingSubStates, true)) {
                // Si el nuevo subestado está en la lista de pendientes, usar "con_pendientes"
                \$entity->{\$overallCol} = 'con pendientes'; // segundo valor del array overall_status.values
            } else {
                // Si no, usar el valor por defecto
                \$entity->{\$overallCol} = \$cfg['overall_status']['default'];
            }
            \$entity->saveQuietly();
        }

        // 10) Registrar en la bitácora
        {$logModelName}::create([
            '{$parentFK}' => \$entity->id,
            'from_state'  => \$fromState,
            'to_state'    => \$toState,
            'from_sub'    => \$fromSubLogged,
            'to_sub'      => \$toSub,
            'type'        => 'state',
            'user_id'     => auth()->id(),
            'payload'     => json_encode(\$entity->getChanges()),
            'comments'    => null,
        ]);
    }
}
PHP;
    }

    /**
     * tplSubstateObserver
     *
     * Observador para registrar cambios en sub-estados.
     * Registra cada cambio de sub-estado como un log separado.
     */

    private function tplSubstateObserver(string $module, string $model, string $parentFK, array $subStates): string
    {
        $modelFQN = "Modules\\{$module}\\Models\\{$model}";
        $logModelClass = "{$model}StepLog";
        $fields = array_column($subStates, 'column');
        $fieldsExport = var_export($fields, true);

        return <<<PHP
<?php

namespace Modules\\{$module}\\Observers;

use {$modelFQN};
use Modules\\{$module}\\Models\\{$logModelClass};

class {$model}SubstateObserver
{
    public function saved({$model} \$entity): void
    {
        \$dirty = \$entity->getChanges();
        \$subKeys = {$fieldsExport};
        \$intersect = array_intersect_key(\$dirty, array_flip(\$subKeys));

        foreach (\$intersect as \$key => \$new) {
            \$original = \$entity->getOriginal(\$key) ?? null;


            {$logModelClass}::create([
                '{$parentFK}' => \$entity->id,
                'from_state'  => null,
                'to_state'    => null,
                'from_sub'    => \$original,
                'to_sub'      => \$new,
                'type'        => 'sub_state',
                'user_id'     => auth()->id(),
                'payload'     => json_encode([\$key => \$new]),
                'comments'    => null,
            ]);
        }
    }
}
PHP;
    }

    private function tplTransitionSubstate(
        string $module,
        string $model,
        string $parentFK,
        string $namespace
    ): string
    {
        $configKey = "modules.{$module}.{$model}_states";
        $logModelClass = "{$model}StepLog";

        return <<<PHP
/**
 * Transiciona un sub-estado correspondiente al estado global actual.
 *
 * @param string \$newValue Nuevo valor para la columna de sub-estado.
 * @param string|null \$comments Comentarios opcionales para registrar en el log
 * @return \$this El objeto del modelo actualizado
 * @throws \InvalidArgumentException
 * @throws \RuntimeException
 */
public function transitionSubstate(string \$newValue, ?string \$comments = null): self
{
    // 1) Cargar config y fallback
    \$cfg = config('{$configKey}');
    if (! is_array(\$cfg)
        || ! isset(\$cfg['sub_states'], \$cfg['auto_transitions'], \$cfg['overall_status'])
    ) {
        \$path = module_path('{$module}', 'Config/{$model}_states.php');
        if (! file_exists(\$path)) {
            throw new \RuntimeException("No se encontró archivo de config: {\$path}");
        }
        \$cfg = require \$path;
    }

    \$subStates = \$cfg['sub_states'];
    \$autoTrans = \$cfg['auto_transitions'];
    \$overall   = \$cfg['overall_status'];
    // namespace desde config, o fallback al inyectado
    \$namespace = \$cfg['namespace'] ?? '{$namespace}';

    // Obtener el estado global actual automáticamente
    \$currentState = class_basename(\$this->state);
    \$key = \$currentState;

    // 2) Validaciones
    if (! isset(\$subStates[\$key])) {
        throw new \InvalidArgumentException("Estado actual '\$key' no tiene sub-estados definidos");
    }
    \$info = \$subStates[\$key];
    if (! in_array(\$newValue, \$info['values'], true)) {
        throw new \InvalidArgumentException("Valor '\$newValue' no válido para sub-estado de {\$key} (columna: {\$info['column']})");
    }

    // Refrescar modelo para asegurar que tenemos el valor previo real en BD
    \$this->refresh();

    // 3) Transacción atómica
    DB::transaction(function() use (
        \$key, \$newValue, \$info, \$subStates, \$comments,
        \$autoTrans, \$overall, \$namespace, \$currentState
    ) {
        // a) Capturar sub-estado anterior
        \$col      = \$info['column'];
        \$oldValue = \$this->{\$col};

        // b) Actualizar columna de sub-estado
        \$this->{\$col} = \$newValue;
        \$this->saveQuietly();

        // c) Log de sub-estado
        {$logModelClass}::create([
            '{$parentFK}'   => \$this->id,
            'from_state'    => \$currentState,
            'to_state'      => \$currentState,
            'from_sub'      => \$oldValue,
            'to_sub'        => \$newValue,
            'type'          => 'sub_state',
            'user_id'       => auth()->id() ?? 0,
            'payload'       => json_encode([\$col => \$newValue]),
            'comments'      => \$comments,
        ]);

        // d) Ajustar overall_status
        \$pending = \$overall['triggers']['pending']['sub_states'] ?? [];
        \$closed  = \$overall['triggers']['closed']['sub_states'] ?? [];
        if (in_array(\$newValue, \$pending, true)) {
            \$this->{\$overall['column']} = 'con pendientes';
            \$this->saveQuietly();
        } elseif (in_array(\$newValue, \$closed, true)) {
            \$this->{\$overall['column']} = 'cerrado';
            \$this->saveQuietly();
        } else {
            // Si no está en ningún trigger, restaurar al estado default
            \$this->{\$overall['column']} = \$overall['default'];
            \$this->saveQuietly();
        }

        // e) Auto-transición global si es final
        if (\$newValue === \$info['final'] && isset(\$autoTrans[\$key])) {
            \$nextKey     = \$autoTrans[\$key];
            \$nextInfo    = \$subStates[\$nextKey] ?? [];
            \$nextDefault = \$nextInfo['default'] ?? null;

            // overall a pendientes
            \$this->{\$overall['column']} = 'con pendientes';
            \$this->saveQuietly();

            \$class = 'Modules\\\\{$module}\\\\States\\\\{$namespace}\\\\\\\\' . \$nextKey;
            \$this->state->transitionTo(\$class);
        }
    });

    // Refrescar el modelo para devolver la versión actualizada
    \$this->refresh();
    return \$this;
}
PHP;
    }

    private function tplTransitionToWithComments(string $module, string $model): string
    {
        $singular = Str::snake(Str::singular($model));
        $parentFK = "{$singular}_id";

        return <<<PHP
/**
 * Realiza una transición de estado global con comentarios opcionales
 *
 * @param string \$stateClass La clase de estado destino
 * @param string|null \$comments Comentarios opcionales para el log
 * @return \$this
 */
public function transitionToWithComments(string \$stateClass, ?string \$comments = null): self
{
    // Realizar la transición normal
    \$this->state->transitionTo(\$stateClass);

    // Si hay comentarios, actualizar el último log
    if (\$comments !== null) {
        \$lastLog = \$this->stepLogs()
            ->where('type', 'state')
            ->orderBy('id', 'desc')
            ->first();

        if (\$lastLog) {
            \$lastLog->update(['comments' => \$comments]);
        }
    }

    return \$this->refresh();
}
PHP;
    }
}
