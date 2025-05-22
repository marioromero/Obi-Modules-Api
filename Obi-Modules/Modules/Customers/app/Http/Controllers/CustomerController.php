<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Customers\Models\Customer;
use App\Http\Controllers\Controller;

class CustomerController extends BaseApiController
{
 
    public function index()
    {
        $paginator = Customer::paginate(15);
        return $this->paginated($paginator, 'Listado de customers');
    }

    public function show(Customer $customer)
    {
        return $this->success($customer, 'Customer obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $customer = Customer::create($data);

        return $this->success($customer, 'Customer creado correctamente', 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate(['name' => 'required|string']);
        $customer->update($data);

        return $this->success($customer, 'Customer actualizado correctamente');
    }

    public function patch(Request $request, Customer $customer)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $customer->update($data);

        return $this->success($customer, 'Customer parcialmente actualizado');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return $this->success(null, 'Customer eliminado correctamente', 204);
    }
}
