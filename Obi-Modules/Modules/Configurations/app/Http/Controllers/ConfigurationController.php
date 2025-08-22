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
        $data = $request->validate([
            'name' => 'required|string|min:1|max:100',
        ]);

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
        return $this->success(null, 'Configuration eliminado correctamente', 200);
    }

    // Devuelve los países configurados
    public function countries()
    {
        // Evitamos firstOrFail para mantener contrato consistente
        $configuration = Configuration::where('type_id', 2)->first();
        if (! $configuration) {
            return $this->error("No existe configuración para 'Global_geography' (type_id = 2).", 422);
        }

        $ids = $configuration->content['countries'] ?? [];
        $countries = Country::whereIn('id', $ids)->get(['id', 'demonym_female']);

        return $this->success($countries, 'Countries from configuration');
    }

    // Actualiza la lista de países
    public function updateCountries(Request $request, UpdateCountries $service)
    {
        $data = $request->validate([
            'countries'   => ['required', 'array'],
            'countries.*' => ['integer', Rule::exists('geography_db.countries', 'id')],
        ]);

        $configuration = Configuration::where('type_id', 2)->first();
        if (! $configuration) {
            return $this->error("No existe configuración para 'Global_geography' (type_id = 2).", 422);
        }

        $config = $service($configuration, $data['countries']);
        return $this->success($config, 'Countries list updated');
    }

    //Responsabilidades de usuario (type_id = 4)
    public function getUserResponsibilities()
    {
        $stepsOrder = ['Denuncio','Programacion','Visita','Presupuesto','Liquidacion','Recaudacion'];

        // 1) Cargar configuración
        $config = Configuration::where('type_id', 4)->first();
        if (! $config) {
            return $this->error("No existe configuración para 'User_responsabilities' (type_id = 4).", 422);
        }

        // 2) Contenido normalizado
        $content = $config->content ?? [];
        if (!is_array($content)) {
            $content = is_string($content) ? (json_decode($content, true) ?: []) : (array) $content;
        }

        // Normalizar IDs que vengan
        $normalizeIds = function ($value): array {
            if (is_string($value)) $value = array_map('trim', explode(',', $value));
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

        // Usuarios ACTIVOS (status_id = 1)
        $activeUsers = TraroUser::select('id','name')
            ->where('status_id', 1)
            ->orderBy('name','asc')
            ->get()
            ->keyBy('id');  // id => model

        $assignedIdsAll = collect($content)
            ->map(fn($v) => (is_array($v) && isset($v['user_assigned'])) ? $v['user_assigned'] : [])
            ->flatten()
            ->pipe($normalizeIds);

        $assignedNames = $assignedIdsAll
            ? TraroUser::whereIn('id', $assignedIdsAll)->pluck('name', 'id') // trae nombres de activos/inactivos
            : collect();

        // Armar respuesta por paso
        $result = [];
        foreach ($stepsOrder as $step) {
            $idsThisStep = [];
            if (isset($content[$step]) && is_array($content[$step])) {
                $idsThisStep = $normalizeIds($content[$step]['user_assigned'] ?? $content[$step]);
            }

            // Asignados (mantener lo que esté en config, aunque algún user esté inactivo)
            $assigned = collect($idsThisStep)->map(fn ($id) => [
                'id'   => $id,
                'name' => $assignedNames[$id] ?? ($activeUsers[$id]->name ?? null),
            ])->values()->all();

            // Disponibles = usuarios activos - ids ya asignados en ESTE paso
            $available = $activeUsers->keys()
                ->diff($idsThisStep)
                ->values()
                ->map(fn ($id) => [
                    'id'   => (int) $id,
                    'name' => $activeUsers[$id]->name,
                ])->all();

            $result[$step] = [
                'user_assigned'  => $assigned,
                'user_available' => $available,
            ];
        }

        return $this->success($result, 'Usuarios asignados y disponibles por paso obtenidos correctamente');
    }

    public function updateUserResponsibilities(Request $request)
    {
        $configuration = Configuration::where('type_id', 4)->first();
        if (! $configuration) {
            return $this->error("No existe configuración para 'User_responsabilities' (type_id = 4).", 422);
        }

        $incoming = $request->input('detail', $request->all());
        if (!is_array($incoming)) $incoming = [];

        $current = $configuration->content ?? [];
        if (!is_array($current)) {
            $current = is_string($current) ? (json_decode($current, true) ?: []) : (array) $current;
        }

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

        $cleanPatch = [];
        foreach ($incoming as $step => $data) {
            $ids = is_array($data) && array_key_exists('user_assigned', $data)
                ? $normalizeIds($data['user_assigned'])
                : $normalizeIds($data);

            $cleanPatch[$step] = ['user_assigned' => $ids];
        }

        $newContent = array_merge($current, $cleanPatch);
        unset($newContent['detail'], $newContent['estado'], $newContent['user_ids']);

        $configuration->content = $newContent;
        $configuration->save();

        return $this->success(
            $configuration->content,
            'Responsabilidades de usuarios actualizadas correctamente'
        );
    }

    public function getColumnsAndCasesByRole(int $roleId, Request $request)
    {
        if ($roleId < 1) {
            return $this->error('ID de rol inválido', 422);
        }
        $roleKey = (string) $roleId;

        $configConnection = (new Configuration)->getConnectionName() ?: config('database.default');

        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'Columns_by_rol')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'Columns_by_rol' en la conexión '{$configConnection}'.", 422);
        }

        $configRow = Configuration::where('type_id', $typeId)->first();
        if (! $configRow) {
            return $this->error("No hay configuración para 'Columns_by_rol' (type_id={$typeId}).", 422);
        }

        $content = $configRow->content ?? [];
        $columns = (isset($content[$roleKey]) && is_array($content[$roleKey])) ? $content[$roleKey] : [];

        if (empty($columns)) {
            return $this->error("No hay columnas configuradas para role_id={$roleKey}.", 422);
        }

        $query = DB::connection('cases_db')->table('v_cases_details');

        $isConsultantRole = ($roleKey === '5');
        $requesterId      = $request->header('X-User-Id') ?? auth()->id();

        if ($isConsultantRole && $requesterId) {
            $query->orderByRaw('CASE WHEN consultant_id = ? THEN 0 ELSE 1 END', [(int) $requesterId]);
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $cases = $rows->map(function ($row) use ($columns) {
            $record = ['id' => $row->id];
            foreach ($columns as $col) {
                $record[$col] = property_exists($row, $col) ? $row->{$col} : null;
            }
            return $record;
        })->values();

        return $this->success([
            'columns' => $columns,
            'cases'   => $cases,
        ], 'Casos por rol obtenidos correctamente');
    }
}
