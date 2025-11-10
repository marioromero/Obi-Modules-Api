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

            // Permitimos "Evento:" si es login o delete
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
                $match = ((int)$r->entity_pk) === $entityId;
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
                    $match = ((int)$cand) === $entityId;
                }
            }

            if ($match) $userLogs[] = $r;
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
                '_id'         => (int)$r->id,
            ];
        }

        // 5) Transformar case_step_logs (si aplica)
        foreach ($caseSteps as $s) {
            [$fecha, $hora] = $this->formatDateTime($s->created_at);
            $usuario         = $this->resolveUserName($s->user_id ?? null);

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
                '_id'         => (int)($s->id ?? 0),
            ];
        }

        // 6) Ordenar por timestamp desc, luego id desc
        usort($items, function ($a, $b) {
            if ($a['_ts'] === $b['_ts']) return $b['_id'] <=> $a['_id'];
            return $b['_ts'] <=> $a['_ts'];
        });

        $items = array_map(fn($x) => Arr::except($x, ['_ts','_id']), $items);

        return $this->success($items, 'Logs por entidad');
    }
}

