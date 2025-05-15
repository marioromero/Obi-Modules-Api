<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\Type;

use App\Http\Controllers\Controller;


class TypeController extends Controller
{


    public function index()
    {
        $data = Type::paginate(15);
        return response()->json($data);
    }

    public function show(Type $type)
    {
        return response()->json($type);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $type = Type::create($data);
        return response()->json($type, 201);
    }

    public function update(Request $request, Type $type)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $type->update($data);
        return response()->json($type);
    }

    public function patch(Request $request, Type $type)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $type->update($data);
        return response()->json($type);
    }

    public function destroy(Type $type)
    {
        $type->delete();
        return response()->noContent();
    }
}
