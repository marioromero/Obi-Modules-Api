<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Customers\Models\CustomerStatus;
use App\Http\Controllers\Controller;


class CustomerStatusController extends BaseApiController
{

    public function index()
    {
        $paginator = CustomerStatus::paginate(15);
        return $this->paginated($paginator, 'Listado de customer-statuses');
    }

    public function show(CustomerStatus $customerStatus)
    {
        return $this->success($customerStatus, 'CustomerStatus obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $customerStatus = CustomerStatus::create($data);

        return $this->success($customerStatus, 'CustomerStatus creado correctamente', 201);
    }

    public function update(Request $request, CustomerStatus $customerStatus)
    {
        $data = $request->validate(['name' => 'required|string']);
        $customerStatus->update($data);

        return $this->success($customerStatus, 'CustomerStatus actualizado correctamente');
    }

    public function patch(Request $request, CustomerStatus $customerStatus)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $customerStatus->update($data);

        return $this->success($customerStatus, 'CustomerStatus parcialmente actualizado');
    }

    public function destroy(CustomerStatus $customerStatus)
    {
        $customerStatus->delete();
        return $this->success(null, 'CustomerStatus eliminado correctamente', 204);
    }
}

