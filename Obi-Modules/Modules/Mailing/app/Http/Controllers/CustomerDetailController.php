<?php

namespace Modules\Mailing\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\CustomerDetail;
use App\Http\Controllers\Controller;


class CustomerDetailController extends Controller
{
 
    public function index()
    {
        $data = CustomerDetail::paginate(15);
        return response()->json($data);
    }

    public function show(CustomerDetail $customerDetail)
    {
        return response()->json($customerDetail);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customerDetail = CustomerDetail::create($data);
        return response()->json($customerDetail, 201);
    }

    public function update(Request $request, CustomerDetail $customerDetail)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customerDetail->update($data);
        return response()->json($customerDetail);
    }

    public function patch(Request $request, CustomerDetail $customerDetail)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $customerDetail->update($data);
        return response()->json($customerDetail);
    }

    public function destroy(CustomerDetail $customerDetail)
    {
        $customerDetail->delete();
        return response()->noContent();
    }
}
