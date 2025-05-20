<?php

namespace Modules\Banks\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Banks\Models\LossAdjuster;
use App\Http\Controllers\Controller;


class LossAdjusterController extends Controller
{

    public function index()
    {
        $data = LossAdjuster::paginate(15);
        return response()->json($data);
    }

    public function show(LossAdjuster $lossAdjuster)
    {
        return response()->json($lossAdjuster);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $lossAdjuster = LossAdjuster::create($data);
        return response()->json($lossAdjuster, 201);
    }

    public function update(Request $request, LossAdjuster $lossAdjuster)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $lossAdjuster->update($data);
        return response()->json($lossAdjuster);
    }

    public function patch(Request $request, LossAdjuster $lossAdjuster)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $lossAdjuster->update($data);
        return response()->json($lossAdjuster);
    }

    public function destroy(LossAdjuster $lossAdjuster)
    {
        $lossAdjuster->delete();
        return response()->noContent();
    }
}
