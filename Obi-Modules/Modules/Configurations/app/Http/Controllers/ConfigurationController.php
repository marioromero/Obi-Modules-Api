<?php

namespace Modules\Configurations\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
use Modules\Configurations\Models\Configuration;
use Modules\Configurations\app\Services\UpdateCountries;
use Illuminate\Validation\Rule;
use Modules\Users\Models\TraroUser;
use Illuminate\Http\Request;
use Modules\Geography\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ConfigurationController extends BaseApiController
{

    public function index()
    {
        $paginator = Configuration::paginate(15);
        return $this->paginated($paginator, 'Listado de configurations');
    }

    public function show(Configuration $configuration)
    {
        return $this->success($configuration, 'Configuration obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $configuration = Configuration::create($data);

        return $this->success($configuration, 'Configuration creado correctamente', 201);
    }

    public function update(Request $request, Configuration $configuration)
    {
        $data = $request->validate(['name' => 'required|string']);
        $configuration->update($data);

        return $this->success($configuration, 'Configuration actualizado correctamente');
    }

    public function patch(Request $request, Configuration $configuration)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $configuration->update($data);

        return $this->success($configuration, 'Configuration parcialmente actualizado');
    }

    public function destroy(Configuration $configuration)
    {
        $configuration->delete();
        return $this->success(null, 'Configuration eliminado correctamente', 204);
    }

    /* ───────────────  Países  (type_id = 2)  ─────────────── */

    /** Devuelve los países configurados */
    public function countries()
    {
        $configuration = Configuration::where('type_id', 2)->firstOrFail();   // Global_geography
        $ids = $configuration->content['countries'] ?? [];

        $countries = Country::whereIn('id', $ids)
                            ->get(['id', 'demonym_female']);

        return $this->success($countries, 'Countries from configuration');
    }

    /** Actualiza la lista de países */
    public function updateCountries(Request $request, UpdateCountries $service)
    {
        $data = $request->validate([
            'countries'   => ['required', 'array'],
            'countries.*' => ['integer', Rule::exists('geography_db.countries', 'id')],
        ]);

        $configuration = Configuration::where('type_id', 2)->firstOrFail();
        $config        = $service($configuration, $data['countries']);

        return $this->success($config, 'Countries list updated');
    }

    /* ───────  Responsabilidades de usuario (type_id = 4)  ─────── */

    public function getUserResponsibilities()
    {
        $config  = Configuration::where('type_id', 4)->firstOrFail();
        $content = $config->content ?? [];

        // 1) Quedarse solo con elementos válidos (arrays con user_assigned[])
        $valid = collect($content)->filter(function ($v) {
            return is_array($v)
                && array_key_exists('user_assigned', $v)
                && is_array($v['user_assigned']);
        });

        // 2) IDs únicos
        $ids = $valid->pluck('user_assigned')
            ->flatten()
            ->filter(fn ($v) => is_numeric($v))
            ->unique()
            ->values();

        // 3) Nombres
        $names = $ids->isEmpty()
            ? collect()
            : TraroUser::whereIn('id', $ids)->pluck('name', 'id');

        // 4) Construir respuesta enriquecida
        $result = [];
        foreach ($valid as $step => $data) {
            $result[$step] = [
                'user_assigned' => collect($data['user_assigned'])->map(fn ($id) => [
                    'id'   => (int) $id,
                    'name' => $names[$id] ?? null,
                ])->values()->all(),
            ];
        }

    return $this->success($result, 'Responsabilidades de usuarios obtenidas correctamente');
}

    public function updateUserResponsibilities(Request $request)
    {
        $configuration = Configuration::where('type_id', 4)->firstOrFail();

        // 1) Obtener el payload real (si viene envuelto en "detail", úsalo)
        $incoming = $request->input('detail', $request->all());
        if (!is_array($incoming)) $incoming = [];

        // 2) Contenido actual como array
        $current = $configuration->content ?? [];
        if (!is_array($current)) {
            $current = is_string($current) ? (json_decode($current, true) ?: []) : (array) $current;
        }

        // 3) Normalizador: dejar SOLO IDs enteros en user_assigned[]
        $normalizeIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (!is_array($value)) return [];
            $ids = [];
            foreach ($value as $item) {
                if (is_array($item) && array_key_exists('id', $item)) {
                    $ids[] = (int) $item['id'];
                } elseif (is_object($item) && isset($item->id)) {
                    $ids[] = (int) $item->id;
                } elseif (is_numeric($item)) {
                    $ids[] = (int) $item;
                }
            }
            return array_values(array_unique(array_filter($ids, fn($v) => $v > 0)));
        };

        // 4) Construir patch limpio SOLO con user_assigned[]
        $cleanPatch = [];
        foreach ($incoming as $step => $data) {
            $ids = is_array($data) && array_key_exists('user_assigned', $data)
                ? $normalizeIds($data['user_assigned'])
                : $normalizeIds($data);

            $cleanPatch[$step] = ['user_assigned' => $ids];
        }

        // 5) Merge por step y limpiar llaves basura
        $newContent = array_merge($current, $cleanPatch);
        unset($newContent['detail'], $newContent['estado'], $newContent['user_ids']);

        // 6) Guardar
        $configuration->content = $newContent;
        $configuration->save();

        return $this->success(
            $configuration->content, // devuelve el content ya limpio
            'Responsabilidades de usuarios actualizadas correctamente'
        );
    }

   public function getColumnsAndCasesByRole(Request $request)
    {
        // 1) Validar role_id
        $data = $request->validate([
            'role_id' => 'required|integer|min:1',
        ]);
        $roleKey = (string) $data['role_id'];

        // 2) Conexión donde viven types/configurations (la del modelo Configuration)
        $configConnection = (new Configuration)->getConnectionName() ?: config('database.default');

        // 3) Resolver type_id del type 'Columns_by_rol' en ESA conexión
        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'Columns_by_rol')
            ->value('id');

        if (! $typeId) {
            throw ValidationException::withMessages([
                'role_id' => "No existe el type 'Columns_by_rol' en la conexión '{$configConnection}'.",
            ]);
        }

        // 4) Traer configuración y columnas para el rol (usar el modelo para respetar su conexión/casts)
        $configRow = Configuration::where('type_id', $typeId)->firstOrFail();
        $content   = $configRow->content ?? [];
        $columns   = (isset($content[$roleKey]) && is_array($content[$roleKey])) ? $content[$roleKey] : [];

        if (empty($columns)) {
            throw ValidationException::withMessages([
                'role_id' => "No hay columnas configuradas para role_id={$roleKey}.",
            ]);
        }

        // 5) Ventana: últimos 2 meses por document_signing_date (no nulos)
        $from = now()->subMonthsNoOverflow(2)->toDateString();
        $to   = now()->toDateString();

        // 6) Query a la vista en cases_db: ordenar más nuevos primero
        $rows = DB::connection('cases_db')
            ->table('v_cases_details')
            ->whereBetween('document_signing_date', [$from, $to])
            ->orderByDesc('document_signing_date')
            ->orderByDesc('id')
            ->get();

        // 7) Recortar cada fila a las columnas pedidas (en orden) + id
        $cases = $rows->map(function ($row) use ($columns) {
            $record = ['id' => $row->id];
            foreach ($columns as $col) {
                // property_exists permite devolver null si la columna existe pero su valor es null
                $record[$col] = property_exists($row, $col) ? $row->{$col} : null;
            }
            return $record;
        })->values();

        // 8) Respuesta estándar
        return $this->success([
            'columns' => $columns,
            'cases'   => $cases,
        ], 'Casos por rol obtenidos correctamente');
    }
}
