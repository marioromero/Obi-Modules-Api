<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateModuleRest extends Command
{
    protected $signature = 'make:module-rest {module} {entities*}';
    protected $description = 'Añade métodos CRUD con lógica predefinida y rutas REST usando controladores existentes';

    public function handle()
    {
        $module   = $this->argument('module');
        $entities = $this->argument('entities');

        foreach ($entities as $entity) {
            // Ruta plural kebab para el URI
            $resourceUri    = Str::plural(Str::kebab($entity));
            // Nombre de parámetro en camelCase para variables y type-hints
            $paramName      = Str::camel($entity);
            $controllerPath = base_path("Modules/{$module}/app/Http/Controllers/{$entity}Controller.php");
            $routesFile     = base_path("Modules/{$module}/routes/api.php");

            // 1) Obtener namespace del modelo
            $modelPath = base_path("Modules/{$module}/Models/{$entity}.php");
            if (file_exists($modelPath)) {
                $modelSrc = file_get_contents($modelPath);
                if (preg_match('/^namespace\s+([^;]+);/m', $modelSrc, $m)) {
                    $modelFqn = "{$m[1]}\\{$entity}";
                } else {
                    throw new RuntimeException("No pude leer el namespace de {$modelPath}");
                }
            } else {
                $this->warn("❗ Modelo no encontrado: {$modelPath}. Usando namespace genérico.");
                $modelFqn = "Modules\\{$module}\\Models\\{$entity}";
            }

            // 2) Verificar existencia del controlador
            if (!file_exists($controllerPath)) {
                $this->error("Controlador no encontrado: {$controllerPath}");
                continue;
            }

            // 3) Leer y agregar imports de Request y Modelo
            $content = file_get_contents($controllerPath);
            $content = preg_replace(
                '/^namespace\s+[^;]+;$/m',
                "$0\n\nuse Illuminate\\Http\\Request;\nuse {$modelFqn};",
                $content,
                1
            );

            // 4) Eliminar métodos CRUD previos (sin head ni options)
            foreach (['index','show','store','update','patch','destroy'] as $method) {
                $pattern = "/public function {$method}\\s*\\([^\\)]*\\)\\s*\\{(?:[^}]*|(?R))*\\}/s";
                $content = preg_replace($pattern, '', $content);
            }

            // 5) Bloque CRUD simplificado
            $crud = <<<PHP

    public function index()
    {
        \$data = {$entity}::paginate(15);
        return response()->json(\$data);
    }

    public function show({$entity} \${$paramName})
    {
        return response()->json(\${$paramName});
    }

    public function store(Request \$request)
    {
        \$data = \$request->validate([
            'name' => 'required|string',
        ]);
        \${$paramName} = {$entity}::create(\$data);
        return response()->json(\${$paramName}, 201);
    }

    public function update(Request \$request, {$entity} \${$paramName})
    {
        \$data = \$request->validate([
            'name' => 'required|string',
        ]);
        \${$paramName}->update(\$data);
        return response()->json(\${$paramName});
    }

    public function patch(Request \$request, {$entity} \${$paramName})
    {
        \$data = \$request->validate([
            'name' => 'sometimes|string',
        ]);
        \${$paramName}->update(\$data);
        return response()->json(\${$paramName});
    }

    public function destroy({$entity} \${$paramName})
    {
        \${$paramName}->delete();
        return response()->noContent();
    }
PHP;
            $content = preg_replace('/}\s*$/', rtrim($crud) . "\n}\n", $content);
            file_put_contents($controllerPath, $content);
            $this->info("✔ CRUD actualizado en {$controllerPath}");

            // 6) Agregar rutas (sin head ni options)
            $nsController = "Modules\\{$module}\\app\\Http\\Controllers";
            $routes  = "\n// REST para {$entity}\n";
            $routes .= "use {$nsController}\\{$entity}Controller;\n";
            $routes .= "Route::get('{$resourceUri}', [{$entity}Controller::class, 'index']);\n";
            $routes .= "Route::get('{$resourceUri}/{" . $paramName . "}', [{$entity}Controller::class, 'show']);\n";
            $routes .= "Route::post('{$resourceUri}', [{$entity}Controller::class, 'store']);\n";
            $routes .= "Route::put('{$resourceUri}/{" . $paramName . "}', [{$entity}Controller::class, 'update']);\n";
            $routes .= "Route::patch('{$resourceUri}/{" . $paramName . "}', [{$entity}Controller::class, 'patch']);\n";
            $routes .= "Route::delete('{$resourceUri}/{" . $paramName . "}', [{$entity}Controller::class, 'destroy']);\n";
            if (!Str::contains(file_get_contents($routesFile), "// REST para {$entity}")) {
                file_put_contents($routesFile, $routes, FILE_APPEND);
                $this->info("✔ Rutas agregadas en {$routesFile}");
            }
        }

        $this->info("✅ Generación finalizada sin head ni options y sin guiones en nombres de variable.");
    }
}
