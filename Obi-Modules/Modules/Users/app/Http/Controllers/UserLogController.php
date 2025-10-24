<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\UserLog;
use Illuminate\Support\Facades\Cache;
use Modules\Users\app\Http\Requests\StoreModelLogsRequest;
use Illuminate\Support\Facades\DB;

class UserLogController extends BaseApiController
{

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
        $data = $request->validated();

        // 1) Resolvemos la "acción" (create/update/delete/login/other) desde events.name
        $action = $this->getEventAction((int)$data['event_id']);

        // 2) Normalizamos details: garantizamos claves before/after según la acción
        $incoming = $data['details']; // puede venir como array o json (el cast del modelo lo soporta)
        $details = ['before' => null, 'after' => null];

        switch ($action) {
            case 'create':
                // si ya viene before/after, respetamos; si no, hacemos after = data
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
                    $details['after']  = $incoming['after']  ?? null; // normalmente null
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
                $details = $incoming;
                break;
        }

        // 3) Guardamos (timestamp lo pone la BD con useCurrent())
        $log = UserLog::create([
            'user_id'  => (int)$data['user_id'],
            'model_id' => (int)$data['model_id'],
            'event_id' => (int)$data['event_id'],
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
}

