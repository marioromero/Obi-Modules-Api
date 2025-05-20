<?php

namespace Modules\Geography\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Geography\Models\Commune;
use App\Http\Controllers\Controller;


class CommuneController extends Controller
{

    public function index()
    {
        $data = Commune::paginate(15);
        return response()->json($data);
    }

    public function show(Commune $commune)
    {
        return response()->json($commune);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $commune = Commune::create($data);
        return response()->json($commune, 201);
    }

    public function update(Request $request, Commune $commune)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $commune->update($data);
        return response()->json($commune);
    }

    public function patch(Request $request, Commune $commune)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $commune->update($data);
        return response()->json($commune);
    }

    public function destroy(Commune $commune)
    {
        $commune->delete();
        return response()->noContent();
    }
}
