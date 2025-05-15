<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cases\Models\AccidentType;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccidentTypeController extends Controller
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
        $data = AccidentType::paginate(15);
        return response()->json($data);
    }

    public function show(AccidentType $accidentType)
    {
        return response()->json($accidentType);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $accidentType = AccidentType::create($data);
        return response()->json($accidentType, 201);
    }

    public function update(Request $request, AccidentType $accidentType)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $accidentType->update($data);
        return response()->json($accidentType);
    }

    public function patch(Request $request, AccidentType $accidentType)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $accidentType->update($data);
        return response()->json($accidentType);
    }

    public function destroy(AccidentType $accidentType)
    {
        $accidentType->delete();
        return response()->noContent();
    }
}
