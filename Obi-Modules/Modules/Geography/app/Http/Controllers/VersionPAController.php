<?php

namespace Modules\Geography\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Geography\Models\VersionPA;
use App\Http\Controllers\Controller;


class VersionPAController extends Controller
{

    public function index()
    {
        $data = VersionPA::paginate(15);
        return response()->json($data);
    }

    public function show(VersionPA $versionPA)
    {
        return response()->json($versionPA);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $versionPA = VersionPA::create($data);
        return response()->json($versionPA, 201);
    }

    public function update(Request $request, VersionPA $versionPA)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $versionPA->update($data);
        return response()->json($versionPA);
    }

    public function patch(Request $request, VersionPA $versionPA)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $versionPA->update($data);
        return response()->json($versionPA);
    }

    public function destroy(VersionPA $versionPA)
    {
        $versionPA->delete();
        return response()->noContent();
    }
}
