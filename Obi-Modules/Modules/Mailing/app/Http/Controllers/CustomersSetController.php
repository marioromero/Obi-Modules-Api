<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\CustomersSet;
use App\Http\Controllers\Controller;

class CustomersSetController extends BaseApiController
{
    
    public function index()
    {
        $paginator = CustomersSet::paginate(15);
        return $this->paginated($paginator, 'Listado de customers-sets');
    }

    public function show(CustomersSet $customersSet)
    {
        return $this->success($customersSet, 'CustomersSet obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $customersSet = CustomersSet::create($data);

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
        return $this->success(null, 'CustomersSet eliminado correctamente', 204);
    }
}
