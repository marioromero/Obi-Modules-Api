<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\UserStatus;

use App\Http\Controllers\Controller;


class UserStatusController extends BaseApiController
{

    public function index()
    {
        $paginator = UserStatus::paginate(15);
        return $this->paginated($paginator, 'Listado de user-statuses');
    }

    public function show(UserStatus $userStatus)
    {
        return $this->success($userStatus, 'UserStatus obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $userStatus = UserStatus::create($data);

        return $this->success($userStatus, 'UserStatus creado correctamente', 201);
    }

    public function update(Request $request, UserStatus $userStatus)
    {
        $data = $request->validate(['name' => 'required|string']);
        $userStatus->update($data);

        return $this->success($userStatus, 'UserStatus actualizado correctamente');
    }

    public function patch(Request $request, UserStatus $userStatus)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $userStatus->update($data);

        return $this->success($userStatus, 'UserStatus parcialmente actualizado');
    }

    public function destroy(UserStatus $userStatus)
    {
        $userStatus->delete();
        return $this->success(null, 'UserStatus eliminado correctamente', 204);
    }
}

