<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cases\Models\CaseStatus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CaseStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    

    /**
     * Store a newly created resource in storage.
     */
    

    /**
     * Show the specified resource.
     */
    

    /**
     * Update the specified resource in storage.
     */
    

    /**
     * Remove the specified resource from storage.
     */
    

    public function index()
    {
        $data = CaseStatus::paginate(15);
        return response()->json($data);
    }

    public function show(CaseStatus $caseStatus)
    {
        return response()->json($caseStatus);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $caseStatus = CaseStatus::create($data);
        return response()->json($caseStatus, 201);
    }

    public function update(Request $request, CaseStatus $caseStatus)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $caseStatus->update($data);
        return response()->json($caseStatus);
    }

    public function patch(Request $request, CaseStatus $caseStatus)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $caseStatus->update($data);
        return response()->json($caseStatus);
    }

    public function destroy(CaseStatus $caseStatus)
    {
        $caseStatus->delete();
        return response()->noContent();
    }
}
