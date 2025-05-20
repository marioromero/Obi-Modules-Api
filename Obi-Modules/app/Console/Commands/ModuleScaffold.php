<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModuleScaffold extends Command
{
    protected $signature = 'module:scaffold {module} {name}';
    protected $description = 'Crea estructura API completa para un recurso dentro de un módulo';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $name   = Str::studly($this->argument('name'));

        if (! is_dir(base_path("Modules/{$module}"))) {
            $this->error("🚫 El módulo '{$module}' no existe. Primero: php artisan module:make {$module}");
            return Command::FAILURE;
        }

        // Sobrescribir rutas para que se generen donde deben
        config([
            'modules.paths.generator.controller.path'      => 'app/Http/Controllers',
            'modules.paths.generator.request.path'         => 'app/Http/Requests',
            'modules.paths.generator.resource.path'        => 'app/Transformers',
            'modules.paths.generator.controller.namespace' => 'App\\Http\\Controllers',
        ]);

        $this->info("📦 Scaffold API para {$name} en módulo {$module}...");

        //
        // 1) Modelo + INYECCIÓN DE TRAIT
        //
        $this->call('module:make-model', [
            'model'  => $name,
            'module' => $module,
        ]);

        // Ruta al archivo del modelo generado
        $modelPath = base_path("Modules/{$module}/Models/{$name}.php");

        if (file_exists($modelPath)) {
            $text = file_get_contents($modelPath);

            // 1.a) Añadir import del trait justo después del namespace
            $text = str_replace(
                "namespace Modules\\{$module}\\Models;",
                "namespace Modules\\{$module}\\Models;\n\nuse Modules\\Core\\app\\Support\\Traits\\DeletionStrategies;",
                $text
            );

            // 1.b) Insertar 'use DeletionStrategies;' dentro de la clase
            //    buscamos la primera llave '{' de la clase y añadimos la línea
            $text = preg_replace(
                '/(class\s+' . preg_quote($name, '/') . '\s+extends\s+Model\s*\{)/',
                "$1\n    use DeletionStrategies;\n",
                $text,
                1
            );

            file_put_contents($modelPath, $text);
        }

        //
        // 2) Controlador API
        //
        $this->call('module:make-controller', [
            'controller' => "{$name}Controller",
            '--api'      => true,
            'module'     => $module,
        ]);

        //
        // 3) Migración
        //
        $table = Str::plural(Str::snake($name));
        $this->call('module:make-migration', [
            'name'   => "create_{$table}_table",
            'module' => $module,
        ]);

        //
        // 4) Requests
        //
        $this->call('module:make-request', [
            'name'   => "Store{$name}Request",
            'module' => $module,
        ]);
        $this->call('module:make-request', [
            'name'   => "Update{$name}Request",
            'module' => $module,
        ]);

        //
        // 5) Transformer (Resource)
        //
        $this->call('module:make-resource', [
            'name'   => "{$name}Resource",
            'module' => $module,
        ]);

        //
        // 6) Factory
        //
        $this->call('module:make-factory', [
            'name'   => "{$name}Factory",
            'module' => $module,
        ]);

        //
        // 7) Seeder
        //
        $this->call('module:make-seed', [
            'name'   => "{$name}Seeder",
            'module' => $module,
        ]);

        $this->info("✅ Scaffold completo para {$name} en módulo {$module}.");
        return Command::SUCCESS;
    }
}
