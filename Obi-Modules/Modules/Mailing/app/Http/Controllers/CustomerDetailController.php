<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\CustomerDetail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Helpers\ColumnMap;

class CustomerDetailController extends BaseApiController
{
 
    public function index()
    {
        $keys = [
            'customer_id',
            'name',
            'lastname',
            'email',
            'tags',
            'commune_name',
            'province_name',
            'region_name',
            'cases_count',
        ];

        $rows = DB::connection('mailing_db')
            ->table('v_customers_mailing')
            ->orderByDesc('customer_id')
            ->get()
            ->map(function ($row) {
                $row->tags = $row->tags ? json_decode($row->tags, true) : [];
                return $row;
            });

        return $this->success([
            'columns' => ColumnMap::translate($keys, 'mailing'),
            'rows'    => $rows,
        ], 'Listado de clientes');
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

