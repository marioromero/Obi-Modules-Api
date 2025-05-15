<?php

namespace Modules\Geography\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Geography\Models\Province;
use App\Http\Controllers\Controller;


class ProvinceController extends Controller
{

    public function index()
    {
        $data = Province::paginate(15);
        return response()->json($data);
    }

    public function show(Province $province)
    {
        return response()->json($province);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $province = Province::create($data);
        return response()->json($province, 201);
    }

    public function update(Request $request, Province $province)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $province->update($data);
        return response()->json($province);
    }

    public function patch(Request $request, Province $province)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $province->update($data);
        return response()->json($province);
    }

    public function destroy(Province $province)
    {
        $province->delete();
        return response()->noContent();
    }
}
