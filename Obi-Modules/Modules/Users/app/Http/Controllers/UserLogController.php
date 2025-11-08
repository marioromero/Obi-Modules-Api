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

        foreach ($rows as $r) {
            [$fecha, $hora] = $this->formatDateTime($r->timestamp);
            $usuario   = $this->resolveUserName($r->user_id);
            $descLines = $this->buildDescriptionFromUserLogRow($r, $map);

            $descripcion = trim(implode("\n", $descLines));
            if ($descripcion === '') continue;
            if (\Illuminate\Support\Str::startsWith($descripcion, 'Evento:')
                && !\Illuminate\Support\Str::contains($descripcion, 'login')) {
                continue;
            }

            $out[] = [
                'date'       => $fecha,             // dd/mm/aaaa
                'time'        => $hora,              // HH:mm:ss
                'user'     => $usuario ?? '----', // fallback
                'description' => $descripcion,
            ];
        }

        return $this->success($out, 'Logs por usuario');
    }

    // Logs por accion
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
            if (\Illuminate\Support\Str::startsWith($descripcion, 'Evento:')
                && !\Illuminate\Support\Str::contains($descripcion, 'login')) {
                continue;
            }

            $out[] = [
                'date'       => $fecha,
                'time'        => $hora,
                'user'     => $usuario ?? '----',
                'description' => $descripcion,
            ];
        }

        return $this->success($out, 'Logs por acción');
    }

    // Logs por entidad (modelId + entityId)
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

        // 2) Filtrar por entity_id (SOLO desde details)
        $userLogs = [];
        foreach ($rows as $r) {
            $det = $this->decodeJson($r->details ?? null);

            // preferimos AFTER.id → BEFORE.id → id (raíz)
            $cand = \Illuminate\Support\Arr::get($det, 'after.id',
                    \Illuminate\Support\Arr::get($det, 'before.id',
                    \Illuminate\Support\Arr::get($det, 'id')));

            // sin id en details => no es un log de entidad (p.ej., auth)
            if (!is_scalar($cand)) continue;

            if ((int)$cand === $entityId) {
                $userLogs[] = $r;
            }
        }

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

        // 4) Transformar user_logs (diffs campo a campo)
        foreach ($userLogs as $r) {
            [$fecha, $hora] = $this->formatDateTime($r->timestamp);
            $usuario        = $this->resolveUserName($r->user_id);
            $descLines      = $this->buildDescriptionFromUserLogRow($r, $map);

            $descripcion = trim(implode("\n", $descLines));
            if ($descripcion === '') continue;

            // Filtra el fallback "Evento: ..." salvo login
            if (\Illuminate\Support\Str::startsWith($descripcion, 'Evento:')
                && !\Illuminate\Support\Str::contains($descripcion, 'login')) {
                continue;
            }

            $items[] = [
                'date'        => $fecha,
                'time'        => $hora,
                'user'        => $usuario ?? '----',
                'description' => $descripcion,
                '_ts'         => $this->tsValue($r->timestamp),
                '_id'         => (int)$r->id,
            ];
        }

        // 5) Transformar case_step_logs (cambios de estado/subestado + payload *_from/_to)
        foreach ($caseSteps as $s) {
            [$fecha, $hora] = $this->formatDateTime($s->created_at);
            $usuario         = $this->resolveUserName($s->user_id ?? null);

            $lines = [];

            // Etiquetas (forzamos español para subestado si no existe en column_map)
            $labelState = $this->labelFor('state', $map, 'case');      // → "Estado"
            $labelSub   = $this->labelFor('substate', $map, 'case');   // si no existe, cae a "Substate"
            if ($labelSub === 'Substate') $labelSub = 'Subestado';

            // Estado
            $fromState = $s->from_state ?? null;
            $toState   = $s->to_state ?? null;
            if ($this->changed($fromState, $toState)) {
                $lines[] = 'Cambió '.$labelState.' de '.$this->prettyValueForDiff('state', $fromState).' a '.$this->prettyValueForDiff('state', $toState);
            }

            // Subestado
            $fromSub = $s->from_sub ?? $s->from_substate ?? null;
            $toSub   = $s->to_sub ?? $s->to_substate ?? null;
            if ($this->changed($fromSub, $toSub)) {
                $lines[] = 'Cambió '.$labelSub.' de '.$this->humanValue($fromSub).' a '.$this->humanValue($toSub);
            }

            // Payload pares *_from/*_to
            $payload = $this->decodeJson($s->payload ?? null);
            if (is_array($payload)) {
                foreach ($payload as $k => $v) {
                    if (\Illuminate\Support\Str::endsWith($k, '_from')) {
                        $base = \Illuminate\Support\Str::beforeLast($k, '_from');
                        $from = $v;
                        $to   = $payload[$base.'_to'] ?? null;
                        if ($this->changed($from, $to)) {
                            $lines[] = 'Cambió '.$this->labelFor($base, $map, 'case').' de '.$this->humanValue($from).' a '.$this->humanValue($to);
                        }
                    }
                }
            }

            // Evita líneas vacías o con ---- a ----
            $lines = array_values(array_filter($lines, function ($ln) {
                return !\Illuminate\Support\Str::contains($ln, '---- a ----');
            }));
            if (empty($lines)) continue;

            $items[] = [
                'date'        => $fecha,
                'time'        => $hora,
                'user'        => $usuario ?? '----',
                'description' => implode("\n", $lines),
                '_ts'         => $this->tsValue($s->created_at),
                '_id'         => (int)($s->id ?? 0),
            ];
        }

        // 6) Ordenar por timestamp desc, luego id desc
        usort($items, function ($a, $b) {
            if ($a['_ts'] === $b['_ts']) return $b['_id'] <=> $a['_id'];
            return $b['_ts'] <=> $a['_ts'];
        });

        // 7) Limpiar metacampos
        $items = array_map(fn($x) => \Illuminate\Support\Arr::except($x, ['_ts','_id']), $items);

        return $this->success($items, 'Logs por entidad');
    }
}

