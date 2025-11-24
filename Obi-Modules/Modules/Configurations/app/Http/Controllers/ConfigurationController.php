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
use Modules\Core\app\Helpers\ColumnMap;

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

        $configConnection = (new Configuration)->getConnectionName() ?: 'configurations_db';

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

        // Decoder seguro para JSON
        $safeJsonDecode = function ($value) {
            if ($value === null || $value === '' || $value === 'null') return null;
            if (is_array($value))  return $value;
            if (is_object($value)) return (array) $value;
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            }
            return null;
        };

        $query = DB::connection('cases_db')->table('v_cases_details');

        $isConsultantRole = ($roleKey === '5');
        $requesterId      = $request->header('X-User-Id') ?? auth()->id();

        if ($isConsultantRole && $requesterId) {
            $query->orderByRaw('CASE WHEN consultant_id = ? THEN 0 ELSE 1 END', [(int) $requesterId]);
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();

        $cases = $rows->map(function ($row) use ($columns, $safeJsonDecode) {
            $record = ['id' => $row->id];

            foreach ($columns as $col) {
                if ($col === 'case_flows_last') {
                    // Mapear case_flows_last (config) ← desde v_cases_details.case_flow_last_json (vista)
                    $record['case_flows_last'] = property_exists($row, 'case_flow_last_json')
                        ? $safeJsonDecode($row->case_flow_last_json)
                        : null;
                } else {
                    $record[$col] = property_exists($row, $col) ? $row->{$col} : null;
                }
            }

            return $record;
        })->values();

        return $this->success([
            'columns' => ColumnMap::translate($columns, 'cases'), // si quieres etiqueta bonita para case_flows_last, agrégala en ColumnMap
            'cases'   => $cases,
        ], 'Casos por rol obtenidos correctamente');
    }

    public function filtersByUsers(TraroUser $user)
    {
        $configConnection = (new Configuration)->getConnectionName();

        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'User_filters')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'User_filters' en la conexión '{$configConnection}'.", 422);
        }

        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'User_filters' (type_id={$typeId}).", 422);
        }

        $content = $row->content ?? [];
        if (! is_array($content)) {
            $content = is_string($content) ? (json_decode($content, true) ?: []) : (array) $content;
        }

        $userKey = (string) $user->id;
        $list = $content[$userKey]['filters']['config_columns'] ?? null;

        if (! is_array($list) || empty($list)) {
            return $this->error("No hay configuraciones para user_id={$user->id}.", 422);
        }

        // Solo definiciones: key, name y color (SIN columns)
        $defs = [];
        foreach ($list as $it) {
            $key   = $it['key']   ?? null;
            $name  = $it['name']  ?? null;
            $color = $it['color'] ?? null;

            if (! $key || ! $name) {
                return $this->error("Filtro inválido en la configuración de user_id={$user->id}.", 422);
            }

            $defs[] = [
                'key'   => (string) $key,
                'name'  => (string) $name,
                'color' => $color !== null ? (string) $color : null,
            ];
        }

        return $this->success([
            'user_id' => (int) $user->id,
            'filters' => [
                'config_columns' => $defs,
            ],
        ], 'Filtros de usuario obtenidos correctamente');
    }


    public function filterCasesByKey(TraroUser $user, string $key)
    {
        $configConnection = (new Configuration)->getConnectionName();

        $typeId = DB::connection($configConnection)
            ->table('types')->where('name', 'User_filters')->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'User_filters' en la conexión '{$configConnection}'.", 422);
        }

        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'User_filters' (type_id={$typeId}).", 422);
        }

        $content = $row->content ?? [];
        if (! is_array($content)) {
            $content = is_string($content) ? (json_decode($content, true) ?: []) : (array) $content;
        }

        $userKey = (string) $user->id;
        $list = $content[$userKey]['filters']['config_columns'] ?? null;

        if (! is_array($list) || empty($list)) {
            return $this->error("No hay configuraciones para user_id={$user->id}.", 422);
        }

        // Buscar el filtro por key
        $item = collect($list)->firstWhere('key', $key);
        if (! $item) {
            return $this->error("No existe el filtro '{$key}' para user_id={$user->id}.", 422);
        }

        $name    = $item['name'] ?? $key;
        $columns = array_values(array_map('strval', (array) ($item['columns'] ?? [])));
        $sqlFrag = (string) ($item['sql'] ?? '');

        if (empty($columns) || $sqlFrag === '') {
            return $this->error("Filtro '{$key}' inválido para user_id={$user->id}.", 422);
        }

        // Armar SELECT (id + columnas del filtro)
        $selectCols = array_merge(['id'], $columns);
        $selectList = implode(',', array_map(fn($c) => "`{$c}`", $selectCols));
        $query      = "SELECT {$selectList} FROM v_cases_details v {$sqlFrag}";

        try {
            $rows = DB::connection('cases_db')->select($query);
        } catch (\Throwable $e) {
            return $this->error("Error al ejecutar el filtro '{$key}': " . $e->getMessage(), 422);
        }

        // Mapear filas
        $cases = array_map(function ($row) use ($selectCols) {
            $record = [];
            foreach ($selectCols as $col) {
                $record[$col] = property_exists($row, $col) ? $row->{$col} : null;
            }
            return $record;
        }, $rows);

        return $this->success([
            'user_id' => (int) $user->id,
            'key'     => (string) $key,
            'name'    => (string) $name,
            'columns' => ColumnMap::translate($columns, 'cases'),
            'cases'   => $cases,
        ], 'Casos del filtro obtenidos correctamente');
    }

    public function getUsersFiltersAndColumns(TraroUser $user, string $paso, ?string $filter = null)
    {
        $step = mb_strtolower($paso, 'UTF-8');
        $key  = $filter;

        // 1) Cargar configuración (type = User_filters)
        $configConnection = (new Configuration)->getConnectionName() ?: 'configurations_db';

        $typeId = DB::connection($configConnection)
            ->table('types')->where('name', 'User_filters')->value('id');

        if (! $typeId) {
            return $this->error("No existe el tipo 'User_filters' en la conexión '{$configConnection}'.", 422);
        }

        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'User_filters' (type_id={$typeId}).", 422);
        }

        $content = $row->content ?? [];
        if (! is_array($content)) {
            $content = is_string($content) ? (json_decode($content, true) ?: []) : (array) $content;
        }

        $userKey = (string) $user->id;

        //Se intenta usar la config propia del usuario para el paso
        $stepCfg = $content[$userKey]['steps'][$step] ?? null;

        // Fallback: si no hay config propia, heredar SOLO default (+ managed_cases) vía User_responsabilities
        if (! $stepCfg) {
            // 1) Cargar User_responsabilities
            $respTypeId = DB::connection($configConnection)
                ->table('types')->where('name', 'User_responsabilities')->value('id');

            if (! $respTypeId) {
                return $this->error("No existe el tipo 'User_responsabilities' en la conexión '{$configConnection}'.", 422);
            }

            $respRow = Configuration::where('type_id', $respTypeId)->first();
            if (! $respRow) {
                return $this->error("No hay configuración para 'User_responsabilities' (type_id={$respTypeId}).", 422);
            }

            $resp = $respRow->content ?? [];
            if (! is_array($resp)) {
                $resp = is_string($resp) ? (json_decode($resp, true) ?: []) : (array) $resp;
            }

            // 2) Map de clave de paso: filtros usa minúsculas
            $stepRespKeyMap = [
                'denuncio'     => 'Denuncio',
                'programacion' => 'Programacion',
                'visita'       => 'Visita',
                'presupuesto'  => 'Presupuesto',
                'liquidacion'  => 'Liquidacion',
                'recaudacion'  => 'Recaudacion',
            ];
            $stepRespKey = $stepRespKeyMap[$step] ?? null;
            if (! $stepRespKey) {
                return $this->error("Paso '{$step}' no reconocido en User_responsabilities.", 422);
            }

            // 3) ¿Usuario asignado al paso?
            $assigned = (array) ($resp[$stepRespKey]['user_assigned'] ?? []);
            if ($assigned && in_array((int) $user->id, $assigned, true)) {
                // Buscar el primer user_id del orden que tenga default en ese paso
                foreach ($assigned as $candidateId) {
                    $candKey     = (string) $candidateId;
                    $candStepCfg = $content[$candKey]['steps'][$step] ?? null;
                    $candDefault = is_array($candStepCfg['default'] ?? null) ? $candStepCfg['default'] : null;

                    if ($candDefault) {
                        // Heredar SOLO default y managed_cases
                        $stepCfg = [
                            'default'       => array_values(array_unique($candDefault)),
                            'filters'       => [],
                            'managed_cases' => $candStepCfg['managed_cases'] ?? null,
                        ];
                        break;
                    }
                }
            }

            // 4) Si aún no hay config ni herencia válida → error
            if (! $stepCfg) {
                return $this->error("No hay configuración para user_id={$user->id} en el paso '{$step}', ni herencia disponible.", 404);
            }
        }

        // 2) Condición por estado (para default y casos ya manejados)
        $stateCond = function (string $s): ?string {
            $label = [
                'denuncio'     => 'Denuncio',
                'programacion' => 'Programacion',
                'visita'       => 'Visita',
                'presupuesto'  => 'Presupuesto',
                'liquidacion'  => 'Liquidacion',
                'recaudacion'  => 'Recaudacion',
            ][$s] ?? null;

            if (! $label) return null;
            return "SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = '{$label}'";
        };

        $baseOrder = 'ORDER BY created_at DESC, id DESC';
        $baseSql   = 'SELECT * FROM v_cases_details v ';

        // 3) phone y user_name desde customers_db.v_customers_details
        $enrichWithCustomerData = function (array $rows): array {
            if (empty($rows)) return $rows;

            $customerIds = [];
            foreach ($rows as $r) {
                if (isset($r->customer_id)) $customerIds[] = (int) $r->customer_id;
            }
            $customerIds = array_values(array_unique(array_filter($customerIds)));
            if (!$customerIds) return $rows;

            $extras = DB::connection('customers_db')
                ->table('v_customers_details')
                ->whereIn('id', $customerIds)
                ->get(['id', 'phone', 'user_name'])
                ->keyBy('id');

            foreach ($rows as $r) {
                $cid = isset($r->customer_id) ? (int) $r->customer_id : null;
                if ($cid && $extras->has($cid)) {
                    $extra = $extras->get($cid);
                    if (!isset($r->phone) || $r->phone === null) {
                        $r->phone = $extra->phone ?? null;
                    }
                    if (!isset($r->user_name) || $r->user_name === null) {
                        $r->user_name = $extra->user_name ?? null;
                    }
                }
            }
            return $rows;
        };

        // 4) id + columnas esperadas en el mismo orden
        $projectRows = function (array $rows, array $columnsEn): array {
            $selectCols = array_values(array_unique(array_merge(['id'], $columnsEn)));
            return array_map(function ($r) use ($selectCols) {
                $out = [];
                foreach ($selectCols as $c) {
                    $out[$c] = property_exists($r, $c) ? $r->{$c} : null;
                }
                return $out;
            }, $rows);
        };
        // Eliminar duplicados
        $dedupRows = function (array $rows): array {
            return collect($rows)->unique('id')->values()->all();
        };

        // Helper para evitar los registros "Test" y deerivados
        $filterOutTest = function (array $rows): array {
            return array_values(array_filter($rows, fn($r) => stripos($r->customer_name ?? '', 'test') === false));
        };

        // 4.1 Calcular managed_cases (reutilizable para default y para filter)
        $calcManagedCases = function (string $step, array $stepCfg, array $defaultColumnsEn) use (
            $stateCond, $baseSql, $baseOrder, $enrichWithCustomerData, $projectRows,$dedupRows,$filterOutTest
        ) {
            $managedMonths = (int)($stepCfg['managed_cases']['months'] ?? 2);
            $targetStep    = $stepCfg['managed_cases']['target_step'] ?? null;

            $managedData = [];

            // Caso especial: Recaudación = "pagados recientes"
           if ($step === 'recaudacion') {
            $whereReca = $stateCond('recaudacion');
            if ($whereReca) {
                $window = "collection_date >= DATE_SUB(NOW(), INTERVAL {$managedMonths} MONTH)";
                // Solo pagados y con fecha de pago
                $status = "LOWER(payment_status) = 'pagado' AND collection_date IS NOT NULL";

                $sql = $baseSql . "WHERE {$whereReca} AND {$status} AND {$window}
                                   ORDER BY collection_date DESC, id DESC";
                try {
                    $managedData = DB::connection('cases_db')->select($sql);
                } catch (\Throwable $e) {
                    $managedData = [];
                }
                $managedData = $dedupRows($managedData);
                $managedData = $filterOutTest($managedData);
                $managedData = $enrichWithCustomerData($managedData);
                $managedData = $projectRows($managedData, $defaultColumnsEn);
            }
            return ['months' => $managedMonths, 'data' => $managedData];
        }

            // Otros pasos: usar target_step
            if ($targetStep) {
                $tWhere = $stateCond($targetStep);
                if ($tWhere) {
                    $tWhere = "({$tWhere}) AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')";
                    $window = "created_at >= DATE_SUB(NOW(), INTERVAL {$managedMonths} MONTH)";

                    $isTargetRecaudacion = (mb_strtolower($targetStep, 'UTF-8') === 'recaudacion');
                    $mOrderBy = $isTargetRecaudacion
                        ? "ORDER BY COALESCE(probable_payment_date, created_at) ASC, id DESC"
                        : $baseOrder;

                    $mSql = $baseSql . "WHERE {$tWhere} AND {$window} {$mOrderBy}";
                    try {
                        $managedData = DB::connection('cases_db')->select($mSql);
                    } catch (\Throwable $e) {
                        $managedData = [];
                    }
                    $managedData = $dedupRows($managedData);
                    $managedData = $filterOutTest($managedData);
                    $managedData = $enrichWithCustomerData($managedData);
                    $managedData = $projectRows($managedData, $defaultColumnsEn);
                }
            }

            return ['months' => $managedMonths, 'data' => $managedData];
        };

        // 5) Filtro específico (si viene {filter} en la ruta)
        if ($key) {
            $filterCfg = collect($stepCfg['filters'] ?? [])->firstWhere('key', $key);
            if (! $filterCfg) {
                return $this->error("No existe el filtro '{$key}' en el paso '{$step}'.", 404);
            }

            $sqlFrag = trim((string)($filterCfg['sql'] ?? ''));
            if ($sqlFrag === '') {
                return $this->error("Filtro '{$key}' inválido (sin SQL).", 422);
            }

            $columnsEn = array_values(array_map('strval', (array)($filterCfg['columns'] ?? [])));
            $columnsEs = ColumnMap::translate($columnsEn, 'cases');
            $querySql  = $baseSql . $sqlFrag;

            try {
                $rows = DB::connection('cases_db')->select($querySql);
            } catch (\Throwable $e) {
                return $this->error("Error al ejecutar el filtro '{$key}': " . $e->getMessage(), 422);
            }

            $rows = $dedupRows($rows);
            $rows = $filterOutTest($rows);
            $rows = $enrichWithCustomerData($rows);
            $rows = $projectRows($rows, $columnsEn);

            // === NUEVO: calcular managed_cases SIN filtrar
            $defaultColumnsEn = array_values(array_unique((array)($stepCfg['default'] ?? [])));
            $managedBlock     = $calcManagedCases($step, $stepCfg, $defaultColumnsEn);

            return $this->success([
                'user_id'      => (int) $user->id,
                'step'         => $step,
                'filter'       => [
                    'key'     => (string) $filterCfg['key'],
                    'name'    => (string) $filterCfg['name'],
                    'color'   => $filterCfg['color'] ?? null,
                    'columns' => $columnsEs,
                ],
                'data'         => $rows,
                'managed_cases'=> $managedBlock,
            ], 'Filtro aplicado', 200);
        }

        // 6) DEFAULT del paso
        $defaultColumnsEn = array_values(array_unique((array)($stepCfg['default'] ?? [])));
        $defaultColumnsEs = ColumnMap::translate($defaultColumnsEn, 'cases');

        $where = $stateCond($step);
        if (! $where) {
            return $this->error("Paso '{$step}' no reconocido para default.", 422);
        }

        // Excluir casos cerrados
        $where = "({$where}) AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')";

        // Reglas especiales para Recaudación y Visita
        $isRecaudacion = ($step === 'recaudacion');
        $isVisita      = ($step === 'visita');

        // ID del usuario que consulta (cast a int para seguridad)
        $userId = (int) $user->id;

        // ID del usuario que consulta (cast a int para seguridad)
        $visitOrder = "ORDER BY
        CASE WHEN consultant_id = {$userId} THEN 0 ELSE 1 END ASC,
        COALESCE(inspection_date, '9999-12-31') ASC,
        COALESCE(schedule_inspection_time, '23:59:59') ASC,
        id DESC";

    $extraWhere = $isRecaudacion
        ? " AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH)"
        : "";

    $orderBy = $isRecaudacion
        ? "ORDER BY COALESCE(probable_payment_date, created_at) ASC, id DESC"
        : ($isVisita ? $visitOrder : $baseOrder);

    $defaultSql = $baseSql . "WHERE {$where}{$extraWhere} {$orderBy}";

        try {
            $defaultData = DB::connection('cases_db')->select($defaultSql);
        } catch (\Throwable $e) {
            return $this->error("Error al ejecutar default del paso '{$step}': " . $e->getMessage(), 422);
        }

        $defaultData = $dedupRows($defaultData);
        $defaultData = $filterOutTest($defaultData);
        $defaultData = $enrichWithCustomerData($defaultData);
        $defaultData = $projectRows($defaultData, $defaultColumnsEn);

        $filtersMeta = array_map(function ($f) {
            return ['key' => $f['key'], 'name' => $f['name'], 'color' => $f['color'] ?? null];
        }, (array)($stepCfg['filters'] ?? []));

        // managed_cases usando función común
        $managedBlock = $calcManagedCases($step, $stepCfg, $defaultColumnsEn);

        return $this->success([
            'user_id'      => (int) $user->id,
            'step'         => $step,
            'default'      => $defaultColumnsEs,
            'filters'      => $filtersMeta,
            'data'         => $defaultData,
            'managed_cases'=> $managedBlock,
        ], 'Default del paso', 200);
    }

    public function getAgentsAvailable()
    {
        $configConnection = (new Configuration)->getConnectionName() ?: 'configurations_db';

        // 1) Obtener type_id de Agent_available
        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'Agent_available')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'Agent_available' en la conexión '{$configConnection}'.", 422);
        }

        // 2) Cargar configuración
        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'Agent_available' (type_id={$typeId}).", 422);
        }

        // 3) Normalizar contenido
        $normalizeContent = function ($value): array {
            if (! is_array($value)) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value   = $decoded ?: [];
                } else {
                    $value = (array) $value;
                }
            }
            return $value;
        };

        // 4) Normalizador de IDs (misma lógica que responsibilities)
        $normalizeIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (! is_array($value)) return [];
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

        $content    = $normalizeContent($row->content ?? []);
        $selectedIds = $normalizeIds(
            $content['user_assigned'] ?? $content['user_available'] ?? $content
        );

        // 5) Usuarios activos
        $activeUsers = TraroUser::select('id', 'name')
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get()
            ->keyBy('id');

        // 6) Asignados (según config)
        $assigned = collect($selectedIds)->map(function ($id) use ($activeUsers) {
            return [
                'id'   => (int) $id,
                'name' => $activeUsers[$id]->name ?? null,
            ];
        })->values()->all();

        // 7) Disponibles = activos - seleccionados
        $available = $activeUsers->keys()
            ->diff($selectedIds)
            ->values()
            ->map(fn ($id) => [
                'id'   => (int) $id,
                'name' => $activeUsers[$id]->name,
            ])->all();

        return $this->success([
            'user_assigned'  => $assigned,
            'user_available' => $available,
        ], 'Ejecutivos disponibles para asignación obtenidos correctamente');
    }

    public function updateAgentsAvailable(Request $request)
    {
        $configConnection = (new Configuration)->getConnectionName() ?: 'configurations_db';

        // 1) type_id
        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'Agent_available')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'Agent_available' en la conexión '{$configConnection}'.", 422);
        }

        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'Agent_available' (type_id={$typeId}).", 422);
        }

        // 2) Normalizador de IDs
        $normalizeIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (! is_array($value)) return [];
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

        // 3) Payload esperado:
        // { "user_assigned": [3,7,8,...] }
        // o directamente: [3,7,8,...]
        $incoming = $request->input('user_assigned', $request->all());

        $ids = is_array($incoming) && array_key_exists('user_assigned', $incoming)
            ? $normalizeIds($incoming['user_assigned'])
            : $normalizeIds($incoming);

        // 4) Guardar con clave 'user_assigned'
        $row->content = ['user_assigned' => $ids];
        $row->save();

        return $this->success(
            $row->content,
            'Ejecutivos disponibles para asignación actualizados correctamente'
        );
    }

    public function getConsultantsAvailable()
    {
        $configConnection = (new Configuration)->getConnectionName() ?: 'configurations_db';

        // 1) Obtener type_id de Consultant_available
        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'Consultant_available')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'Consultant_available' en la conexión '{$configConnection}'.", 422);
        }

        // 2) Cargar configuración
        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'Consultant_available' (type_id={$typeId}).", 422);
        }

        // 3) Normalizar contenido
        $normalizeContent = function ($value): array {
            if (! is_array($value)) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value   = $decoded ?: [];
                } else {
                    $value = (array) $value;
                }
            }
            return $value;
        };

        // 4) Normalizador de IDs
        $normalizeIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (! is_array($value)) return [];
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

        $content     = $normalizeContent($row->content ?? []);
        $selectedIds = $normalizeIds(
            $content['user_assigned'] ?? $content['user_available'] ?? $content
        );

        // 5) Usuarios activos
        $activeUsers = TraroUser::select('id', 'name')
            ->where('status_id', 1)
            ->orderBy('name', 'asc')
            ->get()
            ->keyBy('id');

        // 6) Asignados
        $assigned = collect($selectedIds)->map(function ($id) use ($activeUsers) {
            return [
                'id'   => (int) $id,
                'name' => $activeUsers[$id]->name ?? null,
            ];
        })->values()->all();

        // 7) Disponibles
        $available = $activeUsers->keys()
            ->diff($selectedIds)
            ->values()
            ->map(fn ($id) => [
                'id'   => (int) $id,
                'name' => $activeUsers[$id]->name,
            ])->all();

        return $this->success([
            'user_assigned'  => $assigned,
            'user_available' => $available,
        ], 'Asesores disponibles para asignación obtenidos correctamente');
    }

    public function updateConsultantsAvailable(Request $request)
    {
        $configConnection = (new Configuration)->getConnectionName() ?: 'configurations_db';

        // 1) type_id
        $typeId = DB::connection($configConnection)
            ->table('types')
            ->where('name', 'Consultant_available')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'Consultant_available' en la conexión '{$configConnection}'.", 422);
        }

        $row = Configuration::where('type_id', $typeId)->first();
        if (! $row) {
            return $this->error("No hay configuración para 'Consultant_available' (type_id={$typeId}).", 422);
        }

        // 2) Normalizador de IDs
        $normalizeIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            if (! is_array($value)) return [];
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

        // 3) Payload:
        // { "user_assigned": [5,11,23,31] } o directamente [5,11,23,31]
        $incoming = $request->input('user_assigned', $request->all());

        $ids = is_array($incoming) && array_key_exists('user_assigned', $incoming)
            ? $normalizeIds($incoming['user_assigned'])
            : $normalizeIds($incoming);

        // 4) Guardar
        $row->content = ['user_assigned' => $ids];
        $row->save();

        return $this->success(
            $row->content,
            'Asesores disponibles para asignación actualizados correctamente'
        );
    }
}
