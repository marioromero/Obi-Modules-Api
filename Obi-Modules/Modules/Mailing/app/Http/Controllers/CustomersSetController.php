<?php

namespace Modules\Mailing\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\CustomersSet;
use App\Http\Controllers\Controller;

class CustomersSetController extends Controller
{
    public function index()
    {
        $data = CustomersSet::paginate(15);
        return response()->json($data);
    }

    public function show(CustomersSet $customersSet)
    {
        return response()->json($customersSet);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customersSet = CustomersSet::create($data);
        return response()->json($customersSet, 201);
    }

    public function update(Request $request, CustomersSet $customersSet)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customersSet->update($data);
        return response()->json($customersSet);
    }

    public function patch(Request $request, CustomersSet $customersSet)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $customersSet->update($data);
        return response()->json($customersSet);
    }

    public function destroy(CustomersSet $customersSet)
    {
        $customersSet->delete();
        return response()->noContent();
    }
}
