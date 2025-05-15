<?php

namespace Modules\Geography\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Geography\Models\Region;
use App\Http\Controllers\Controller;

class RegionController extends Controller
{

    public function index()
    {
        $data = Region::paginate(15);
        return response()->json($data);
    }

    public function show(Region $region)
    {
        return response()->json($region);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $region = Region::create($data);
        return response()->json($region, 201);
    }

    public function update(Request $request, Region $region)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $region->update($data);
        return response()->json($region);
    }

    public function patch(Request $request, Region $region)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $region->update($data);
        return response()->json($region);
    }

    public function destroy(Region $region)
    {
        $region->delete();
        return response()->noContent();
    }
}
