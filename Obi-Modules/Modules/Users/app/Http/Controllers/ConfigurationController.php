<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\Configuration;

use App\Http\Controllers\Controller;


class ConfigurationController extends Controller
{


    public function index()
    {
        $data = Configuration::paginate(15);
        return response()->json($data);
    }

    public function show(Configuration $configuration)
    {
        return response()->json($configuration);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $configuration = Configuration::create($data);
        return response()->json($configuration, 201);
    }

    public function update(Request $request, Configuration $configuration)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $configuration->update($data);
        return response()->json($configuration);
    }

    public function patch(Request $request, Configuration $configuration)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $configuration->update($data);
        return response()->json($configuration);
    }

    public function destroy(Configuration $configuration)
    {
        $configuration->delete();
        return response()->noContent();
    }
}
