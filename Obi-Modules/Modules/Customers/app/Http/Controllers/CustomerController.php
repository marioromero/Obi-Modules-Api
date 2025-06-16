<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Customers\app\Http\Requests\StoreCustomerRequest;
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

    public function store(StoreCustomerRequest $request)   // ← Form Request
    {
        // El FormRequest ya hizo la validación y devuelve solo campos permitidos
        $customer = Customer::create($request->validated());

        return $this->success($customer, 'Cliente creado correctamente', 201);
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

    public function search(Request $request)
    {
        $dni   = $request->query('dni');
        $email = $request->query('email');

        $customer = Customer::when($dni,   fn($q) => $q->where('dni',   $dni))
                            ->when($email, fn($q) => $q->orWhere('email', $email))
                            ->first();

        return $customer
            ? $this->success($customer, 'Customer encontrado')
            : $this->success(null, 'No existe', 204);
    }
}

