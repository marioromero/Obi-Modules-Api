<?php

namespace Modules\Customers\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Models\Customer;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
 
    public function index()
    {
        $data = Customer::paginate(15);
        return response()->json($data);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customer = Customer::create($data);
        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $customer->update($data);
        return response()->json($customer);
    }

    public function patch(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $customer->update($data);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->noContent();
    }
}
