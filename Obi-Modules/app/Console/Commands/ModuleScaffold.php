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
        $name = Str::studly($this->argument('name'));

        $this->info("📦 Generando estructura API para '{$name}' en módulo '{$module}'");

        // 1. Modelo
        $this->call('module:make-model', [
            'model' => $name,
            'module' => $module,
        ]);

        // 2. Controlador API
        $this->call('module:make-controller', [
            'controller' => "{$module}/{$name}Controller",
            '--api' => true,
            'module' => $module,
        ]);

        // 3. Migración
        $table = Str::plural(Str::snake($name));
        $this->call('module:make-migration', [
            'name' => "create_{$table}_table",
            'module' => $module,
        ]);

        // 4. Form Requests
        $this->call('module:make-request', [
            'name' => "Store{$name}Request",
            'module' => $module,
        ]);

        $this->call('module:make-request', [
            'name' => "Update{$name}Request",
            'module' => $module,
        ]);

        // 5. API Resource
        $this->call('module:make-resource', [
            'name' => "{$name}Resource",
            'module' => $module,
        ]);

        // 6. Factory
        $this->call('module:make-factory', [
            'name' => "{$name}Factory",
            'module' => $module,
        ]);

        // 7. Seeder
        $this->call('module:make-seed', [
            'name' => "{$name}Seeded",
            'module' => $module,
        ]);

        $this->info("✅ Scaffold API completo generado para {$name} en módulo {$module}.");
    }

}
