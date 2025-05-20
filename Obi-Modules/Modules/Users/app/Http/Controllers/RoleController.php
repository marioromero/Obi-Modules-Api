<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\Role;

use App\Http\Controllers\Controller;


class RoleController extends Controller
{


    public function index()
    {
        $data = Role::paginate(15);
        return response()->json($data);
    }

    public function show(Role $role)
    {
        return response()->json($role);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $role = Role::create($data);
        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $role->update($data);
        return response()->json($role);
    }

    public function patch(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $role->update($data);
        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->noContent();
    }
}
