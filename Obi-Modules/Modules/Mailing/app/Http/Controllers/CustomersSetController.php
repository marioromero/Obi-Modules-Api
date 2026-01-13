<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\CustomersSet;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Helpers\ColumnMap;

class CustomersSetController extends BaseApiController
{
    
    public function index()
    {
        $keys = [
            'name',
            'created_at',
            'recipients_count',
            'last_used_at',
            'created_by_name',
        ];

        $rows = DB::connection('mailing_db')
            ->table('v_customers_sets_list')
            ->orderByDesc('id')
            ->get();

        return $this->success([
            'columns' => ColumnMap::translate($keys, 'mailing'),
            'rows'    => $rows,
        ], 'Listado de sets');
    }    

    public function show(CustomersSet $customersSet)
    {
        return $this->success($customersSet, 'CustomersSet obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $customersSet = CustomersSet::create([
            'name'    => $data['name'],
            'user_id' => auth()->id(),
        ]);

        return $this->success($customersSet, 'CustomersSet creado correctamente', 201);
    }

    public function update(Request $request, CustomersSet $customersSet)
    {
        $data = $request->validate(['name' => 'required|string']);
        $customersSet->update($data);

        return $this->success($customersSet, 'CustomersSet actualizado correctamente');
    }

    public function patch(Request $request, CustomersSet $customersSet)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $customersSet->update($data);

        return $this->success($customersSet, 'CustomersSet parcialmente actualizado');
    }

    public function destroy(CustomersSet $customersSet)
    {
        $customersSet->delete();
        return $this->success(null, 'Set eliminado correctamente', 200);
    }


    public function storeWithCustomers(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:50',
            'customers'   => 'required|array|min:1',
            'customers.*' => 'integer',
            'user_id'     => 'nullable|integer',
        ]);

        $userId = auth()->id() ?? ($data['user_id'] ?? null);
        if (! $userId) {
            return $this->error('No hay usuario autenticado (user_id es requerido si no usas auth).', 422);
        }

        return DB::connection('mailing_db')->transaction(function () use ($data, $userId) {

            $set = CustomersSet::create([
                'name'    => $data['name'],
                'user_id' => $userId,
            ]);

            $rows = collect($data['customers'])
                ->unique()
                ->map(fn ($customerId) => [
                    'customer_id'     => (int) $customerId,
                    'customer_set_id' => (int) $set->id,
                ])->values()->toArray();

            DB::connection('mailing_db')->table('customer_detail')->insert($rows);

            return $this->success([
                'customer_set_id'  => $set->id,
                'recipients_count' => count($rows),
            ], 'Set creado y clientes asignados correctamente', 201);
        });
    }
}

