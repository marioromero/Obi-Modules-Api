<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModuleScaffold extends Command
{
    protected $signature = 'module:scaffold {module} {name}';
    protected $description = 'Crea modelo, controller, requests, resource, factory, seeder y migración para una entidad dentro de un módulo nWidart, corrigiendo namespaces.';

    /* ---------- AUXILIAR: corrige la línea "namespace …;" ---------------- */
    private function fixNamespaceCase(string $file): void
    {
        if (! is_file($file)) {
            return;
        }

        $txt = file_get_contents($file);

        $txt = preg_replace_callback(
            '/^namespace\s+([^;]+);/m',
            function ($m) {
                $ns      = $m[1];
                $search  = [
                    'Database\\Factories',
                    'Database\\Seeders',
                    'Database\\',
                    'App\\',
                ];
                $replace = [
                    'database\\factories',
                    'database\\seeders',
                    'database\\',
                    'app\\',
                ];
                return 'namespace ' . str_replace($search, $replace, $ns) . ';';
            },
            $txt,
            1
        );

        file_put_contents($file, $txt);
    }

    private function fixNamespaceInDirectory(string $pattern): void
    {
        foreach (glob($pattern) as $php) {
            $this->fixNamespaceCase($php);
        }
    }

    /* ------------------------------- HANDLE ------------------------------ */
    public function handle()
    {
        $module = Str::studly($this->argument('module')); // ej.: Caquita
        $name   = Str::studly($this->argument('name'));   // ej.: Foo

        if (! is_dir(base_path("Modules/{$module}"))) {
            $this->error("🚫 El módulo '{$module}' no existe. Primero: php artisan module:make {$module}");
            return Command::FAILURE;
        }

        /* --- Sobrescribe paths / namespaces deseados --- */
        config([
            'modules.paths.generator.controller.path'      => 'app/Http/Controllers',
            'modules.paths.generator.controller.namespace' => 'app\\Http\\Controllers',

            'modules.paths.generator.request.path'         => 'app/Http/Requests',
            'modules.paths.generator.request.namespace'    => 'app\\Http\\Requests',

            'modules.paths.generator.resource.path'        => 'app/Transformers',
            'modules.paths.generator.resource.namespace'   => 'app\\Transformers',

            'modules.paths.generator.factory.path'         => 'database/factories',
            'modules.paths.generator.factory.namespace'    => 'database\\factories',

            'modules.paths.generator.seed.path'            => 'database/seeders',
            'modules.paths.generator.seed.namespace'       => 'database\\seeders',
        ]);

        $this->info("📦 Scaffold API para {$name} en módulo {$module} …");

        /* ========== 1) MODELO (+ trait) ========== */
        $this->call('module:make-model', [
            'model'  => $name,
            'module' => $module,
        ]);

        $modelPath = base_path("Modules/{$module}/Models/{$name}.php");
        if (is_file($modelPath)) {
            $txt = file_get_contents($modelPath);
            $txt = str_replace(
                "namespace Modules\\{$module}\\Models;",
                "namespace Modules\\{$module}\\Models;\n\nuse Modules\\Core\\app\\Support\\Traits\\DeletionStrategies;",
                $txt
            );
            $txt = preg_replace(
                '/(class\s+' . preg_quote($name, '/') . '\s+extends\s+Model\s*\{)/',
                "$1\n    use DeletionStrategies;\n",
                $txt,
                1
            );
            file_put_contents($modelPath, $txt);
        }

        /* ========== 2) CONTROLLER API ========== */
        $this->call('module:make-controller', [
            'controller' => "{$name}Controller",
            '--api'      => true,
            'module'     => $module,
        ]);
        $this->fixNamespaceCase(
            base_path("Modules/{$module}/app/Http/Controllers/{$name}Controller.php")
        );

        /* ========== 3) MIGRACIÓN ========== */
        $table = Str::plural(Str::snake($name));
        $this->call('module:make-migration', [
            'name'   => "create_{$table}_table",
            'module' => $module,
        ]);

        /* ========== 4) REQUESTS ========== */
        foreach (['Store', 'Update'] as $prefix) {
            $request = "{$prefix}{$name}Request";
            $this->call('module:make-request', [
                'name'   => $request,
                'module' => $module,
            ]);
            $this->fixNamespaceCase(
                base_path("Modules/{$module}/app/Http/Requests/{$request}.php")
            );
        }

        /* ========== 5) RESOURCE / TRANSFORMER ========== */
        $this->call('module:make-resource', [
            'name'   => "{$name}Resource",
            'module' => $module,
        ]);
        $this->fixNamespaceCase(
            base_path("Modules/{$module}/app/Transformers/{$name}Resource.php")
        );

        /* ========== 6) FACTORY ========== */
        // 👉 se pasa SOLO $name; el generador añade “Factory”
        $this->call('module:make-factory', [
            'name'   => $name,
            'module' => $module,
        ]);
        $this->fixNamespaceCase(
            base_path("Modules/{$module}/database/factories/{$name}Factory.php")
        );

        /* ========== 7) SEEDER ========== */
        $this->call('module:make-seed', [
            'name'   => "{$name}Seeder",
            'module' => $module,
        ]);
        $this->fixNamespaceCase(
            base_path("Modules/{$module}/database/seeders/{$name}Seeder.php")
        );

        /* ========== 8) AJUSTA ARCHIVOS BASE DEL MÓDULO ========== */
        $this->fixNamespaceInDirectory(base_path("Modules/{$module}/app/Http/Controllers/*.php"));
        $this->fixNamespaceInDirectory(base_path("Modules/{$module}/database/seeders/*.php"));

        $this->info("✅ Scaffold completo para {$name} en módulo {$module}.");
        return Command::SUCCESS;
    }
}
