<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\Department;
use App\Http\Controllers\Controller;


class DepartmentController extends BaseApiController
{

    public function index()
    {
        $paginator = Department::paginate(15);
        return $this->paginated($paginator, 'Listado de departments');
    }

    public function show(Department $department)
    {
        return $this->success($department, 'Department obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $department = Department::create($data);

        return $this->success($department, 'Department creado correctamente', 201);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate(['name' => 'required|string']);
        $department->update($data);

        return $this->success($department, 'Department actualizado correctamente');
    }

    public function patch(Request $request, Department $department)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $department->update($data);

        return $this->success($department, 'Department parcialmente actualizado');
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return $this->success(null, 'Department eliminado correctamente', 204);
    }
}

