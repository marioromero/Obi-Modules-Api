<?php

namespace Modules\Banks\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Banks\Models\Insurer;
use App\Http\Controllers\Controller;

class InsurerController extends Controller
{

    public function index()
    {
        $data = Insurer::paginate(15);
        return response()->json($data);
    }

    public function show(Insurer $insurer)
    {
        return response()->json($insurer);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $insurer = Insurer::create($data);
        return response()->json($insurer, 201);
    }

    public function update(Request $request, Insurer $insurer)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $insurer->update($data);
        return response()->json($insurer);
    }

    public function patch(Request $request, Insurer $insurer)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $insurer->update($data);
        return response()->json($insurer);
    }

    public function destroy(Insurer $insurer)
    {
        $insurer->delete();
        return response()->noContent();
    }
}
