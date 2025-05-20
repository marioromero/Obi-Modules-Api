<?php

namespace Modules\Customers\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Models\CustomerStatus;
use App\Http\Controllers\Controller;


class CustomerStatusController extends Controller
{

    public function index()
    {
        $data = CustomerStatus::paginate(15);
        return response()->json($data);
    }

    public function show(CustomerStatus $customerStatus)
    {
        return response()->json($customerStatus);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customerStatus = CustomerStatus::create($data);
        return response()->json($customerStatus, 201);
    }

    public function update(Request $request, CustomerStatus $customerStatus)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customerStatus->update($data);
        return response()->json($customerStatus);
    }

    public function patch(Request $request, CustomerStatus $customerStatus)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $customerStatus->update($data);
        return response()->json($customerStatus);
    }

    public function destroy(CustomerStatus $customerStatus)
    {
        $customerStatus->delete();
        return response()->noContent();
    }
}
