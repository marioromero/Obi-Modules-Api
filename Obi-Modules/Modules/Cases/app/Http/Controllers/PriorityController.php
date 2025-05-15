<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cases\Models\Priority;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PriorityController extends Controller
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
        $data = Priority::paginate(15);
        return response()->json($data);
    }

    public function show(Priority $priority)
    {
        return response()->json($priority);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $priority = Priority::create($data);
        return response()->json($priority, 201);
    }

    public function update(Request $request, Priority $priority)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $priority->update($data);
        return response()->json($priority);
    }

    public function patch(Request $request, Priority $priority)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $priority->update($data);
        return response()->json($priority);
    }

    public function destroy(Priority $priority)
    {
        $priority->delete();
        return response()->noContent();
    }
}
