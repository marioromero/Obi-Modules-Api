<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
use Modules\Customers\app\Http\Requests\UpdateCustomerRequest;
use Modules\Customers\app\Resources\CustomerResource;
use Illuminate\Http\Request;
use Modules\Customers\app\Http\Requests\StoreCustomerRequest;
use Modules\Customers\Models\Customer;
use App\Http\Controllers\Controller;
use Modules\Customers\Models\CustomerDetail;

class CustomerController extends BaseApiController
{
 
    public function index()
    {
        // 1) Trae todos los registros y todos sus atributos visibles
        $customers = Customer::all();

        // 2) Devuelve la data sin pasarla por el Resource
        return $this->success($customers, 'Listado de clientes');
    }
    public function show(int $id)
    {
        $customer = CustomerDetail::findOrFail($id);    // ← lee la vista

        return $this->success(
            $customer,
            'Cliente obtenido correctamente'
        );
    }

    public function store(StoreCustomerRequest $request)   // ← Form Request
    {
        // El FormRequest ya hizo la validación y devuelve solo campos permitidos
        $customer = Customer::create($request->validated());

        return $this->success($customer, 'Cliente creado correctamente', 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        // `validated()` trae sólo los campos realmente enviados
        $customer->fill($request->validated())->save();

        return $this->success($customer, 'Cliente actualizado correctamente');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return $this->success(null,'Cliente eliminado exitosamente',200);
    }

    public function search(Request $request)
    {
        $dni   = $request->query('dni');
        $email = $request->query('email');

        $customer = Customer::when($dni,   fn($q) => $q->where('dni',   $dni))
                            ->when($email, fn($q) => $q->orWhere('email', $email))
                            ->first();

        return $customer
            ? $this->success($customer, 'Cliente encontrado')
            : $this->success(null, 'No existe', 204);
    }

    public function customersByName(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        // Normalizamos a minúsculas el término
        $term = mb_strtolower($request->query('q'), 'UTF-8');

        $results = Customer::query()
            ->select(['id'])
            ->selectRaw("CONCAT(name, ' ', lastname) AS text")
            ->whereRaw(
                "LOWER(CONCAT(name, ' ', lastname)) LIKE ?",
                ["%{$term}%"]
            )
            ->orderBy('name')
            ->limit(15)
            ->get();

        return $this->success($results, 'Clientes encontrados');
    }

    public function customersByDni(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:20',
        ]);

        // 1) Le quitamos los puntos y espacios al término
        $raw  = $request->query('q');
        $term = str_replace(['.', ' '], '', trim($raw));
        // ahora "20.008.648-1" → "20008648-1"

        // 2) Buscamos en la BD (que almacena e.g. "20008648-1")
        $results = Customer::query()
            ->select(['id'])
            ->selectRaw("CONCAT(dni, ' – ', name, ' ', lastname) AS text")
            ->where('dni', 'LIKE', "%{$term}%")
            ->orderBy('dni')
            ->limit(15)
            ->get();

        return $this->success($results, 'Clientes encontrados por DNI');
    }

}

