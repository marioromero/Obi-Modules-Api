<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateModuleRest extends Command
{
    protected $signature   = 'make:module-rest {module} {entities*}';
    protected $description = 'Actualiza/crea métodos CRUD (BaseApiController) y añade rutas REST en los controladores de un módulo.';

    /* --------------------------------------------------------------------
     | Elimina **todas** las definiciones de un método en el código dado
     | (balanceando llaves para soportar try/catch anidados).
     *-------------------------------------------------------------------*/
    private static function stripMethod(string $code, string $method): string
    {
        $pattern = '/public\s+function\s+' . $method . '\s*\(/i';

        while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];                       // posición donde empieza "public function ..."
            $brace = strpos($code, '{', $start);     // llave de apertura
            if ($brace === false) break;

            $level = 1; $i = $brace + 1;
            $len   = strlen($code);
            while ($i < $len && $level > 0) {
                $ch = $code[$i];
                if ($ch === '{') $level++;
                if ($ch === '}') $level--;
                $i++;
            }
            // $i apunta al carácter después de la '}' de cierre
            $code = substr($code, 0, $start) . substr($code, $i);
        }
        return $code;
    }

    public function handle(): void
    {
        $module   = Str::studly($this->argument('module'));
        $entities = array_map([Str::class, 'studly'], $this->argument('entities'));

        foreach ($entities as $entity) {

            /* ---------------- paths ---------------- */
            $ctrl = base_path("Modules/{$module}/app/Http/Controllers/{$entity}Controller.php");
            $mdl  = base_path("Modules/{$module}/Models/{$entity}.php");
            $web  = base_path("Modules/{$module}/routes/api.php");

            $uri   = Str::plural(Str::kebab($entity));
            $param = Str::camel($entity);

            $modelNS = "Modules\\{$module}\\Models\\{$entity}";
            if (is_file($mdl) &&
                preg_match('/^namespace\s+([^;]+);/m', file_get_contents($mdl), $m)) {
                $modelNS = "{$m[1]}\\{$entity}";
            }

            if (! is_file($ctrl)) {
                $this->error("🚫 Falta controlador: {$ctrl}");
                continue;
            }

            $code = file_get_contents($ctrl);

            /* -------- imports únicos -------- */
            foreach ([
                'Illuminate\Http\Request',
                $modelNS,
                'Modules\Core\App\Http\BaseApiController',
            ] as $use) {
                if (! str_contains($code, "use {$use};")) {
                    $code = preg_replace('/^namespace\s+[^;]+;$/m', "$0\nuse {$use};", $code, 1);
                }
            }

            /* -------- extiende BaseApiController -------- */
            $code = preg_replace(
                '/class\s+' . $entity . 'Controller\s+extends\s+[^{\s]+/',
                "class {$entity}Controller extends BaseApiController",
                $code,
                1
            );

            /* -------- limpia traits/imports antiguos -------- */
            $code = str_replace(
                ['use ApiResponse;', 'use Modules\\Core\\app\\Support\\Traits\\ApiResponse;', 'use Illuminate\\Support\\Facades\\Log;'],
                '',
                $code
            );

            /* -------- elimina TODAS las versiones previas de cada método -------- */
            foreach (['index','show','store','update','patch','destroy'] as $m) {
                $code = self::stripMethod($code, $m);
            }

            /* -------- bloque CRUD limpio -------- */
$crud = <<<PHP

    public function index()
    {
        \$paginator = {$entity}::paginate(15);
        return \$this->paginated(\$paginator, 'Listado de {$uri}');
    }

    public function show({$entity} \${$param})
    {
        return \$this->success(\${$param}, '{$entity} obtenido correctamente');
    }

    public function store(Request \$request)
    {
        \$data   = \$request->validate(['name' => 'required|string']);
        \${$param} = {$entity}::create(\$data);

        return \$this->success(\${$param}, '{$entity} creado correctamente', 201);
    }

    public function update(Request \$request, {$entity} \${$param})
    {
        \$data = \$request->validate(['name' => 'required|string']);
        \${$param}->update(\$data);

        return \$this->success(\${$param}, '{$entity} actualizado correctamente');
    }

    public function patch(Request \$request, {$entity} \${$param})
    {
        \$data = \$request->validate(['name' => 'sometimes|string']);
        \${$param}->update(\$data);

        return \$this->success(\${$param}, '{$entity} parcialmente actualizado');
    }

    public function destroy({$entity} \${$param})
    {
        \${$param}->delete();
        return \$this->success(null, '{$entity} eliminado correctamente', 204);
    }
PHP;

            $code = preg_replace('/}\s*$/', rtrim($crud) . "\n}\n", $code);
            file_put_contents($ctrl, $code);
            $this->info("✔ CRUD actualizado en {$ctrl}");

            /* -------- rutas REST: añade solo si falta -------- */
            $marker = "// REST para {$entity}";
            if (! str_contains(file_get_contents($web), $marker)) {
                $ns = "Modules\\{$module}\\app\\Http\\Controllers";
                $add = "\n{$marker}\nuse {$ns}\\{$entity}Controller;\n";
                foreach ([
                    ['get',    'index',   ''],
                    ['get',    'show',    '/{'.$param.'}'],
                    ['post',   'store',   ''],
                    ['put',    'update',  '/{'.$param.'}'],
                    ['patch',  'patch',   '/{'.$param.'}'],
                    ['delete', 'destroy', '/{'.$param.'}'],
                ] as [$verb,$met,$suf]) {
                    $add .= "Route::{$verb}('{$uri}{$suf}', [{$entity}Controller::class, '{$met}']);\n";
                }
                file_put_contents($web, $add, FILE_APPEND);
                $this->info("✔ Rutas REST añadidas en {$web}");
            }
        }

        $this->info("✅ Finalizado: sin métodos duplicados.");
    }
}
