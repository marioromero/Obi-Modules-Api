<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\CustomerDetail;
use App\Http\Controllers\Controller;


class CustomerDetailController extends BaseApiController
{
 
    public function index()
    {
        $paginator = CustomerDetail::paginate(15);
        return $this->paginated($paginator, 'Listado de customer-details');
    }

    public function show(CustomerDetail $customerDetail)
    {
        return $this->success($customerDetail, 'CustomerDetail obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $customerDetail = CustomerDetail::create($data);

        return $this->success($customerDetail, 'CustomerDetail creado correctamente', 201);
    }

    public function update(Request $request, CustomerDetail $customerDetail)
    {
        $data = $request->validate(['name' => 'required|string']);
        $customerDetail->update($data);

        return $this->success($customerDetail, 'CustomerDetail actualizado correctamente');
    }

    public function patch(Request $request, CustomerDetail $customerDetail)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $customerDetail->update($data);

        return $this->success($customerDetail, 'CustomerDetail parcialmente actualizado');
    }

    public function destroy(CustomerDetail $customerDetail)
    {
        $customerDetail->delete();
        return $this->success(null, 'CustomerDetail eliminado correctamente', 204);
    }
}
