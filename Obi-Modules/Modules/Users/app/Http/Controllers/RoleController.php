<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\Role;

use App\Http\Controllers\Controller;


class RoleController extends BaseApiController
{

    public function index()
    {
        $paginator = Role::paginate(15);
        return $this->paginated($paginator, 'Listado de roles');
    }

    public function show(Role $role)
    {
        return $this->success($role, 'Role obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $role = Role::create($data);

        return $this->success($role, 'Role creado correctamente', 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate(['name' => 'required|string']);
        $role->update($data);

        return $this->success($role, 'Role actualizado correctamente');
    }

    public function patch(Request $request, Role $role)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $role->update($data);

        return $this->success($role, 'Role parcialmente actualizado');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return $this->success(null, 'Role eliminado correctamente', 204);
    }
}

