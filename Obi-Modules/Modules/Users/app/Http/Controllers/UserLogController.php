<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\UserLog;
use Illuminate\Support\Facades\Cache;
use Modules\Users\app\Http\Requests\StoreModelLogsRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\Users\app\Traits\UserLogHelperTrait;

class UserLogController extends BaseApiController
{
    use UserLogHelperTrait;

    public function index()
    {
        $paginator = UserLog::paginate(15);
        return $this->paginated($paginator, 'Listado de user-logs');
    }

    public function show(UserLog $userLog)
    {
        return $this->success($userLog, 'UserLog obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $userLog = UserLog::create($data);

        return $this->success($userLog, 'UserLog creado correctamente', 201);
    }

    public function update(Request $request, UserLog $userLog)
    {
        $data = $request->validate(['name' => 'required|string']);
        $userLog->update($data);

        return $this->success($userLog, 'UserLog actualizado correctamente');
    }

    public function patch(Request $request, UserLog $userLog)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $userLog->update($data);

        return $this->success($userLog, 'UserLog parcialmente actualizado');
    }

    public function destroy(UserLog $userLog)
    {
        $userLog->delete();
        return $this->success(null, 'UserLog eliminado correctamente', 204);
    }

    public function save(StoreModelLogsRequest $request)
    {
        // 0) Payload base
        $data = $request->validated();
        $data['user_id'] = $data['user_id'] ?? null; // evita undefined key

        // Si viene "create_case" pero en realidad es transición/subestado → forzar update_case.
        try {
            $modelId = (int)($data['model_id'] ?? 0);
            $eventId = (int)($data['event_id'] ?? 0);

            if ($modelId === 1 && $eventId === 1) { // 1 = case

                $incoming = $data['details'] ?? null;
                $det = is_array($incoming) ? $incoming : (json_decode((string)$incoming, true) ?: []);

                $before = (isset($det['before']) && is_array($det['before'])) ? $det['before'] : [];
                $after  = (isset($det['after'])  && is_array($det['after']))  ? $det['after']  : [];

                // 1) Si hay before/after y cambió state → update
                $bState = $before['state'] ?? null;
                $aState = $after['state']  ?? null;

                if (is_string($bState) && is_string($aState) && $bState !== $aState) {
                    $data['event_id'] = 2; // update_case
                }

                // 2) Si cambió algún *_status típico → update
                if ($data['event_id'] === 1 && (!empty($before) || !empty($after))) {
                    $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

                    foreach ($keys as $k) {
                        if (!is_string($k)) continue;

                        $isStatus = str_ends_with($k, '_status') || in_array($k, ['substate','signature_status','denounce_status','scheduling_status','visit_status','budget_status','decision_status','payment_status','overall_status'], true);
                        if (!$isStatus) continue;

                        $bv = $before[$k] ?? null;
                        $av = $after[$k]  ?? null;

                        if ($bv !== $av) {
                            $data['event_id'] = 2; // update_case
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // no romper nada
        }

        // 1) Acción
        $action = $this->getEventAction((int)$data['event_id']);

        // 2) Normalizar details
        $incoming = $data['details'];
        $details = ['before' => null, 'after' => null];

        switch ($action) {
            case 'create':
                if (is_array($incoming) && (array_key_exists('before', $incoming) || array_key_exists('after', $incoming))) {
                    $details['before'] = $incoming['before'] ?? null;
                    $details['after']  = $incoming['after']  ?? null;
                } else {
                    $details['after']  = $incoming ?? null;
                }
                break;

            case 'update':
                $details['before'] = is_array($incoming) ? ($incoming['before'] ?? null) : null;
                $details['after']  = is_array($incoming) ? ($incoming['after']  ?? null) : null;
                break;

            case 'delete':
                if (is_array($incoming) && (array_key_exists('before', $incoming) || array_key_exists('after', $incoming))) {
                    $details['before'] = $incoming['before'] ?? null;
                    $details['after']  = $incoming['after']  ?? null;
                } else {
                    $details['before'] = $incoming ?? null;
                }
                break;

            case 'login':
                if (is_array($incoming) && (array_key_exists('before', $incoming) || array_key_exists('after', $incoming))) {
                    $details['before'] = $incoming['before'] ?? null;
                    $details['after']  = $incoming['after']  ?? null;
                } else {
                    $details['after']  = $incoming ?? null;
                }
                break;

            default:
                if (is_string($incoming)) {
                    $decoded = json_decode($incoming, true);
                    $details = is_array($decoded) ? $decoded : ['after' => $incoming];
                } else {
                    $details = is_array($incoming) ? $incoming : ['after' => $incoming];
                }
                break;
        }

        $details = $this->normalizeDetailsTimestamps(
        $details,
        isset($data['model_id']) ? (int)$data['model_id'] : 0
        );

        // 3) Guardar
        $log = UserLog::create([
            'user_id'  => isset($data['user_id'])  ? (int)$data['user_id']  : null,
            'model_id' => isset($data['model_id']) ? (int)$data['model_id'] : null,
            'event_id' => isset($data['event_id']) ? (int)$data['event_id'] : null,
            'details'  => $details,
        ]);

        return $this->success($log, 'Log guardado correctamente', 201);
    }

    private function getEventAction(int $eventId): string
    {
        $name = Cache::remember("events:name:{$eventId}", 300, function () use ($eventId) {
            return DB::connection('users_db')
                ->table('events')
                ->where('id', $eventId)
                ->value('name') ?? '';
        });

        $prefix = strtolower(strtok($name, '_'));

        return match ($prefix) {
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'login'  => 'login',
            default  => 'other',
        };
    }

   // Logs por ID de usuario
public function logsByUser(int $userId): \Illuminate\Http\JsonResponse
{
    if ($userId <= 0) {
        return $this->error('El parámetro userId debe ser numérico y mayor a 0.', 422);
    }

    $rows = DB::connection('users_db')
        ->table('user_logs')
        ->where('user_id', $userId)
        ->orderByDesc('timestamp')
        ->orderByDesc('id')
        ->get();

    $map = $this->columnMap();
    $out = [];

    // Cache local para “hidratar before” en updates:
    // key = "{model_id}:{entity_id}"  value = last after array
    $lastAfterByEntity = [];

    foreach ($rows as $r) {
        // 1) Decode details
        $det = $this->decodeJson($r->details ?? null);

        $before = is_array($det['before'] ?? null) ? $det['before'] : null;
        $after  = is_array($det['after']  ?? null) ? $det['after']  : null;

        // 2) Detectar entity_id de forma simple
        $entityId = null;
        if (is_array($after) && isset($after['id'])) {
            $entityId = (int) $after['id'];
        } elseif (is_array($before) && isset($before['id'])) {
            $entityId = (int) $before['id'];
        } elseif (isset($det['id'])) {
            $entityId = (int) $det['id'];
        } elseif (is_array($after) && isset($after['entity_id'])) {
            $entityId = (int) $after['entity_id'];
        } elseif (is_array($before) && isset($before['entity_id'])) {
            $entityId = (int) $before['entity_id'];
        }

        $modelId = (int) ($r->model_id ?? 0);
        $key     = ($modelId > 0 && $entityId > 0) ? ($modelId . ':' . $entityId) : null;

        // 3) PARCHE: si es update y before viene vacío, lo “hidratamos” con el after anterior
        $eventId = (int) ($r->event_id ?? 0);

        $beforeEmpty =
            $before === null ||
            (is_array($before) && (count($before) === 0 || (count($before) === 1 && array_key_exists('id', $before))));

        if ($eventId === 2 && $beforeEmpty && $key && isset($lastAfterByEntity[$key]) && is_array($lastAfterByEntity[$key])) {
            $before = $lastAfterByEntity[$key];
            $det['before'] = $before;
        }

        // 4) Construir descripción usando el details (posiblemente parchado)
        //    Importante: mantenemos la forma original para no romper tu trait
        $rForBuild = clone $r;
        $rForBuild->details = $det;

        [$fecha, $hora] = $this->formatDateTime($r->timestamp);
        $usuario   = $this->resolveUserName($r->user_id);
        $descLines = $this->buildDescriptionFromUserLogRow($rForBuild, $map);

        $descripcion = trim(implode("\n", $descLines));
        if ($descripcion === '') {
            // Aun así actualizamos el lastAfter si existe
            if ($key && is_array($after) && !empty($after)) {
                $lastAfterByEntity[$key] = $after;
            }
            continue;
        }

        // Permitimos "Evento:" si es login o delete
        if (
            Str::startsWith($descripcion, 'Evento:')
            && !Str::contains(strtolower($descripcion), 'login')
            && !Str::contains(strtolower($descripcion), 'delete')
        ) {
            if ($key && is_array($after) && !empty($after)) {
                $lastAfterByEntity[$key] = $after;
            }
            continue;
        }

        $out[] = [
            'date'        => $fecha,
            'time'        => $hora,
            'user'        => $usuario ?? '----',
            'model_id'    => $modelId,
            'event_id'    => $eventId ?: null,
            'description' => $descripcion,
        ];

        // 5) Guardar after actual para el próximo log de esa misma entidad
        if ($key && is_array($after) && !empty($after)) {
            $lastAfterByEntity[$key] = $after;
        }
    }

    return $this->success($out, 'Logs por usuario');
}



    // Logs por acción
    public function logsByAction(int $eventId): \Illuminate\Http\JsonResponse
    {
        if ($eventId <= 0) {
            return $this->error('El parámetro eventId debe ser numérico y mayor a 0.', 422);
        }

        $rows = DB::connection('users_db')
            ->table('user_logs')
            ->where('event_id', $eventId)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->get();

        $map = $this->columnMap();
        $out = [];

        foreach ($rows as $r) {
            [$fecha, $hora] = $this->formatDateTime($r->timestamp);
            $usuario   = $this->resolveUserName($r->user_id);
            $descLines = $this->buildDescriptionFromUserLogRow($r, $map);

            $descripcion = trim(implode("\n", $descLines));
            if ($descripcion === '') continue;

            if (Str::startsWith($descripcion, 'Evento:')
                && !Str::contains($descripcion, 'login')
                && !Str::contains(strtolower($descripcion), 'delete')) {
                continue;
            }

            $out[] = [
                'date'        => $fecha,
                'time'        => $hora,
                'user'        => $usuario ?? '----',
                'model_id'    => (int) ($r->model_id ?? 0),
                'event_id'    => (int) ($r->event_id ?? 0),
                'description' => $descripcion,
            ];
        }

        return $this->success($out, 'Logs por acción');
    }

   // Logs por entidad (modelId + entityId) combinando user_logs y case_step_logs (si aplica)
    public function logsByEntity(int $modelId, int $entityId): \Illuminate\Http\JsonResponse
    {
        if ($modelId <= 0 || $entityId <= 0) {
            return $this->error('modelId y entityId deben ser numéricos y mayores a 0.', 422);
        }

        // 1) Traer logs base de users_db
        $rows = DB::connection('users_db')
            ->table('user_logs')
            ->where('model_id', $modelId)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->get();

        // 2) Filtrar por entity_id buscando SIEMPRE en details.before/after/id
        $userLogs = [];
        foreach ($rows as $r) {
            $match = false;

            if (isset($r->entity_pk) && $r->entity_pk) {
                $match = ((int) $r->entity_pk) === $entityId;
            } else {
                $det    = $this->decodeJson($r->details ?? null);
                $before = is_array($det['before'] ?? null) ? $det['before'] : [];
                $after  = is_array($det['after']  ?? null) ? $det['after']  : [];

                $cand = Arr::get($after, 'id',
                    Arr::get($before, 'id',
                    Arr::get($det, 'id',
                    Arr::get($after, 'entity_id',
                    Arr::get($before, 'entity_id',
                    Arr::get($det, 'entity_id'))))));

                if (is_scalar($cand)) {
                    $match = ((int) $cand) === $entityId;
                }
            }

            if ($match) $userLogs[] = $r;
        }

        // FIX #1 (VISUAL): reparar "creates falsos" (before=null) usando el after anterior del mismo entity
        // Esto NO toca BD: solo ajusta $r->details en memoria para que los diffs sean reales.
        usort($userLogs, function ($a, $b) {
            $ta = $this->tsValue($a->timestamp ?? null);
            $tb = $this->tsValue($b->timestamp ?? null);
            if ($ta === $tb) return ((int) $a->id) <=> ((int) $b->id);
            return $ta <=> $tb; // ASC
        });

        $prevAfterByEntity = []; // [entityId => afterArray]
        foreach ($userLogs as $r) {
            $det      = $this->decodeJson($r->details ?? null);
            $before   = $det['before'] ?? null;
            $after    = $det['after']  ?? null;
            $beforeA  = is_array($before) ? $before : [];
            $afterA   = is_array($after)  ? $after  : [];

            $eid = (int) (
                ($afterA['id'] ?? $afterA['entity_id'] ?? $beforeA['id'] ?? $beforeA['entity_id'] ?? 0)
            );

            if ($eid > 0) {
                // si before viene vacío/null, pero ya existe un after previo => lo usamos como before
                if (empty($beforeA) && !empty($afterA) && isset($prevAfterByEntity[$eid])) {
                    $det['before'] = $prevAfterByEntity[$eid];
                    $r->details    = $det; // reinyecta para buildDescriptionFromUserLogRow()
                }

                // actualizar último after conocido
                if (!empty($afterA)) {
                    $prevAfterByEntity[$eid] = $afterA;
                }
            }
        }

        // volver a DESC (como venían desde DB) para mostrar
        usort($userLogs, function ($a, $b) {
            $ta = $this->tsValue($a->timestamp ?? null);
            $tb = $this->tsValue($b->timestamp ?? null);
            if ($ta === $tb) return ((int) $b->id) <=> ((int) $a->id);
            return $tb <=> $ta; // DESC
        });

        // 3) ¿Es 'case'? -> traer case_step_logs
        $isCase = false;
        try {
            $name = DB::connection('users_db')
                ->table('model_logs')
                ->where('id', $modelId)
                ->value('name');
            $isCase = ($name === 'case');
        } catch (\Throwable $e) {
            $isCase = false;
        }

        $caseSteps = [];
        if ($isCase) {
            $caseSteps = DB::connection('cases_db')
                ->table('case_step_logs')
                ->where('case_id', $entityId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();
        }

        $map   = $this->columnMap();
        $items = [];

        // 4) Transformar user_logs
        foreach ($userLogs as $r) {
            [$fecha, $hora] = $this->formatDateTime($r->timestamp);
            $usuario   = $this->resolveUserName($r->user_id);
            $descLines = $this->buildDescriptionFromUserLogRow($r, $map);

            $descripcion = trim(implode("\n", $descLines));
            if ($descripcion === '') continue;

            if (Str::startsWith($descripcion, 'Evento:')
                && !Str::contains($descripcion, 'login')
                && !Str::contains(strtolower($descripcion), 'delete')) {
                continue;
            }

            $items[] = [
                'date'        => $fecha,
                'time'        => $hora,
                'user'        => $usuario ?? '----',
                'model_id'    => (int) ($r->model_id ?? 0),
                'event_id'    => (int) ($r->event_id ?? 0),
                'description' => $descripcion,
                '_ts'         => $this->tsValue($r->timestamp),
                '_id'         => (int) $r->id,
            ];
        }

        // ─────────────────────────────────────────────────────────────
        // DEDUPE: si un case_step_log coincide con un user_log (mismo segundo ±1 y mismo to_state),
        // no lo mostramos para evitar duplicidad visual.
        // ─────────────────────────────────────────────────────────────
        $userLogBuckets = []; // [sec => [to_state_lower => true] | ['__any__' => true]]
        foreach ($userLogs as $r) {
            $det   = $this->decodeJson($r->details ?? null);
            $after = is_array($det['after'] ?? null) ? $det['after'] : [];

            $stateRaw = $after['state'] ?? null;
            $toState  = null;
            if (is_string($stateRaw) && $stateRaw !== '') {
                $parts   = preg_split('#\\\\#', $stateRaw);
                $toState = $parts ? end($parts) : $stateRaw;
            }

            // ✅ FIX #2: tsValue() ya está en segundos (NO dividir por 1000)
            $sec = (int) $this->tsValue($r->timestamp);

            if ($toState) {
                $userLogBuckets[$sec][mb_strtolower((string) $toState, 'UTF-8')] = true;
            } else {
                $userLogBuckets[$sec]['__any__'] = true;
            }
        }

        // 5) Transformar case_step_logs (si aplica)
        foreach ($caseSteps as $s) {
            [$fecha, $hora] = $this->formatDateTime($s->created_at);
            $usuario        = $this->resolveUserName($s->user_id ?? null);

            // DEDUPE: si hay user_log equivalente en el mismo segundo (±1) con mismo to_state, omitimos el step_log
            // ✅ FIX #2: tsValue() ya está en segundos (NO dividir por 1000)
            $stepSec     = (int) $this->tsValue($s->created_at);
            $stepToState = mb_strtolower((string) ($s->to_state ?? ''), 'UTF-8');

            $isDupe = false;
            for ($i = -1; $i <= 1; $i++) {
                $sec = $stepSec + $i;
                if (!isset($userLogBuckets[$sec])) continue;

                if ($stepToState !== '' && isset($userLogBuckets[$sec][$stepToState])) {
                    $isDupe = true;
                    break;
                }
                if (isset($userLogBuckets[$sec]['__any__'])) {
                    $isDupe = true;
                    break;
                }
            }

            if ($isDupe) {
                continue;
            }

            $lines = [];
            // Estado
            $fromState = $s->from_state ?? null;
            $toState   = $s->to_state ?? null;
            if ($this->changed($fromState, $toState)) {
                $lines[] = 'Cambió '.$this->labelFor('state', $map).' de '.$this->humanValue($fromState).' a '.$this->humanValue($toState);
            }
            // Subestado
            $fromSub = $s->from_sub ?? $s->from_substate ?? null;
            $toSub   = $s->to_sub ?? $s->to_substate ?? null;
            if ($this->changed($fromSub, $toSub)) {
                $lines[] = 'Cambió '.$this->labelFor('substate', $map).' de '.$this->humanValue($fromSub).' a '.$this->humanValue($toSub);
            }
            // Payload pares *_from/*_to
            $payload = $this->decodeJson($s->payload ?? null);
            if (is_array($payload)) {
                foreach ($payload as $k => $v) {
                    if (Str::endsWith($k, '_from')) {
                        $base = Str::beforeLast($k, '_from');
                        $from = $v;
                        $to   = $payload[$base.'_to'] ?? null;
                        if ($this->changed($from, $to)) {
                            $label = $this->labelFor($base, $map);
                            $label = str_replace(' (ID)', '', $label);
                            $lines[] = 'Cambió '.$label.' de '.$this->humanValue($from).' a '.$this->humanValue($to);
                        }
                    }
                }
            }

            // Si no hay nada útil, no añadimos
            if (empty($lines) || (count($lines) === 1 && Str::contains($lines[0], '---- a ----'))) {
                continue;
            }

            $items[] = [
                'date'        => $fecha,
                'time'        => $hora,
                'user'        => $usuario ?? '----',
                'model_id'    => (int) $modelId,
                'event_id'    => null,
                'description' => implode("\n", $lines),
                '_ts'         => $this->tsValue($s->created_at),
                '_id'         => (int) ($s->id ?? 0),
            ];
        }

        // 6) Ordenar por timestamp desc, luego id desc
        usort($items, function ($a, $b) {
            if ($a['_ts'] === $b['_ts']) return $b['_id'] <=> $a['_id'];
            return $b['_ts'] <=> $a['_ts'];
        });

        // ✅ MERGE SIMPLE: colapsa duplicados visuales de transición (Estado + subestados)
        // Solo afecta logsByEntity (salida), no toca cómo se guardan los logs.
        $merged = [];
        foreach ($items as $it) {
            $last = end($merged);

            if (!$last) {
                $merged[] = $it;
                continue;
            }

            // Ventana corta (2s)
            $dt = abs((int)$it['_ts'] - (int)$last['_ts']);
            $sameActor = (($it['user'] ?? '----') === ($last['user'] ?? '----'));
            $sameModel = ((int)$it['model_id'] === (int)$last['model_id']);

            $descA = (string)($last['description'] ?? '');
            $descB = (string)($it['description'] ?? '');

            $isTransitionA =
                Str::contains($descA, 'Cambió Estado')
                || Str::contains($descA, ' - estado')
                || Str::contains($descA, 'Cambió Substate')
                || Str::contains($descA, 'Cambió Subestado');

            $isTransitionB =
                Str::contains($descB, 'Cambió Estado')
                || Str::contains($descB, ' - estado')
                || Str::contains($descB, 'Cambió Substate')
                || Str::contains($descB, 'Cambió Subestado');

            // Merge solo si es claramente la misma transición
            if ($sameModel && $sameActor && $isTransitionA && $isTransitionB && $dt <= 2) {

                $linesA = array_filter(array_map('trim', explode("\n", $descA)));
                $linesB = array_filter(array_map('trim', explode("\n", $descB)));

                $seen = [];
                $out  = [];

                foreach (array_merge($linesA, $linesB) as $ln) {
                    $k = mb_strtolower($ln, 'UTF-8');
                    if (isset($seen[$k])) continue;
                    $seen[$k] = true;
                    $out[] = $ln;
                }

                $last['description'] = implode("\n", $out);
                $last['_ts'] = max((int)$last['_ts'], (int)$it['_ts']);
                $last['_id'] = max((int)$last['_id'], (int)$it['_id']);

                array_pop($merged);
                $merged[] = $last;
                continue;
            }

            $merged[] = $it;
        }

        $items = $merged;

        $items = array_map(fn($x) => Arr::except($x, ['_ts','_id']), $items);

        return $this->success($items, 'Logs por entidad');
    }

    //Normaliza created_at / updated_at dentro de details[before] y details[after]
    private function normalizeDetailsTimestamps(array $details, int $modelId): array
    {
        // 1) Resolver nombre de modelo desde model_logs (usa tu propia lógica)
        $modelName = null;
        try {
            $modelName = DB::connection('users_db')
                ->table('model_logs')
                ->where('id', $modelId)
                ->value('name');
        } catch (\Throwable $e) {
            $modelName = null;
        }

        if (!$modelName) {
            // Si no sabemos qué modelo es, solo aplicamos formateo suave (por si acaso)
            return $this->simpleNormalizeDetailsTimestamps($details);
        }

        $key = strtolower(trim($modelName)); // ej: "case", "customer", "bank", ...

        // 2) Mapear a conexión + tabla de la entidad real
        $map = [
            'case'      => ['cases_db',      'cases'],
            'customer'  => ['customers_db',  'customers'],
            'user'      => ['users_db',      'users'],
            'bank'      => ['banks_db',      'banks'],
            'insurer'   => ['banks_db',      'insurers'],
            'loss_adjuster' => ['banks_db',  'loss_adjusters'],
            // agrega aquí otros modelos si los logueas (agreements, accident_types, etc.)
        ];

        if (!isset($map[$key])) {
            // Si no sabemos dónde vive, caemos al formateo suave
            return $this->simpleNormalizeDetailsTimestamps($details);
        }

        [$conn, $table] = $map[$key];

        // 3) Descubrir el ID de la entidad desde details
        $entityId = null;

        if (isset($details['after']) && is_array($details['after'])) {
            $entityId = $details['after']['id'] ?? $details['after']['entity_id'] ?? null;
        }
        if (!$entityId && isset($details['before']) && is_array($details['before'])) {
            $entityId = $details['before']['id'] ?? $details['before']['entity_id'] ?? null;
        }

        $entityId = (int) $entityId;
        if ($entityId <= 0) {
            // sin id, no podemos consultar BD → formateo suave
            return $this->simpleNormalizeDetailsTimestamps($details);
        }

        // 4) Leer created_at / updated_at reales desde la tabla de la entidad
        $row = null;
        try {
            $row = DB::connection($conn)
                ->table($table)
                ->where('id', $entityId)
                ->select(['created_at', 'updated_at'])
                ->first();
        } catch (\Throwable $e) {
            $row = null;
        }

        if (!$row) {
            // Si no existe la fila (raro), al menos formateamos lo que llegue
            return $this->simpleNormalizeDetailsTimestamps($details);
        }

        $tz      = config('app.timezone', 'America/Santiago');
        $created = null;
        $updated = null;

        try {
            if (!empty($row->created_at)) {
                $created = Carbon::parse($row->created_at)->setTimezone($tz)->format('d/m/Y H:i:s');
            }
        } catch (\Throwable $e) {}

        try {
            if (!empty($row->updated_at)) {
                $updated = Carbon::parse($row->updated_at)->setTimezone($tz)->format('d/m/Y H:i:s');
            }
        } catch (\Throwable $e) {}

        // 5) Sobrescribir en before/after para que SIEMPRE coincidan con la entidad real
        foreach (['before', 'after'] as $side) {
            if (!isset($details[$side]) || !is_array($details[$side])) {
                continue;
            }

            if ($created !== null) {
                $details[$side]['created_at'] = $created;
            }
            if ($updated !== null) {
                $details[$side]['updated_at'] = $updated;
            }
        }

        return $details;
    }

    //formatea created_at/updated_at a d/m/Y H:i:s America/Santiago.
    private function simpleNormalizeDetailsTimestamps(array $details): array
    {
        $tz = config('app.timezone', 'America/Santiago');

        foreach (['before', 'after'] as $side) {
            if (!isset($details[$side]) || !is_array($details[$side])) {
                continue;
            }

            foreach (['created_at', 'updated_at'] as $field) {
                if (
                    !array_key_exists($field, $details[$side]) ||
                    $details[$side][$field] === null ||
                    $details[$side][$field] === ''
                ) {
                    continue;
                }

                try {
                    $dt = Carbon::parse($details[$side][$field])->setTimezone($tz);
                    $details[$side][$field] = $dt->format('d/m/Y H:i:s');
                } catch (\Throwable $e) {
                    // dejamos el valor tal cual
                }
            }
        }

        return $details;
    }
}

