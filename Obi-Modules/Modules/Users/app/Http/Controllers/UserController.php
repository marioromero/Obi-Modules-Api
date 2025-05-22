<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\User;

use App\Http\Controllers\Controller;


class UserController extends BaseApiController
{

    public function index()
    {
        $paginator = User::paginate(15);
        return $this->paginated($paginator, 'Listado de users');
    }

    public function show(User $user)
    {
        return $this->success($user, 'User obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $user = User::create($data);

        return $this->success($user, 'User creado correctamente', 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['name' => 'required|string']);
        $user->update($data);

        return $this->success($user, 'User actualizado correctamente');
    }

    public function patch(Request $request, User $user)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $user->update($data);

        return $this->success($user, 'User parcialmente actualizado');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->success(null, 'User eliminado correctamente', 204);
    }
}
