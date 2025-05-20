<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cases\Models\CaseEntity;
use App\Http\Controllers\Controller;


class CaseController extends Controller
{

    public function index()
    {
        $data = CaseEntity::paginate(15);
        return response()->json($data);
    }

    public function show(CaseEntity $case)
    {
        return response()->json($case);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $case = CaseEntity::create($data);
        return response()->json($case, 201);
    }

    public function update(Request $request, CaseEntity $case)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $case->update($data);
        return response()->json($case);
    }

    public function patch(Request $request, CaseEntity $case)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $case->update($data);
        return response()->json($case);
    }

    public function destroy(CaseEntity $case)
    {
        $case->delete();
        return response()->noContent();
    }
}
