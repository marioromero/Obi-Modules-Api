<?php

namespace Modules\Mailing\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\Department;
use App\Http\Controllers\Controller;


class DepartmentController extends Controller
{

    public function index()
    {
        $data = Department::paginate(15);
        return response()->json($data);
    }

    public function show(Department $department)
    {
        return response()->json($department);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $department = Department::create($data);
        return response()->json($department, 201);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $department->update($data);
        return response()->json($department);
    }

    public function patch(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $department->update($data);
        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->noContent();
    }
}
