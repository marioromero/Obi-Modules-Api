<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\UserLog;

use App\Http\Controllers\Controller;


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
}
