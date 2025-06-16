<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;
use Modules\Cases\Models\Agreement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgreementController extends BaseApiController
{
    
    public function index()
    {
        $paginator = Agreement::paginate(15);
        return $this->paginated($paginator, 'Listado de agreements');
    }

    public function show(Agreement $agreement)
    {
        return $this->success($agreement, 'Agreement obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $agreement = Agreement::create($data);

        return $this->success($agreement, 'Agreement creado correctamente', 201);
    }

    public function update(Request $request, Agreement $agreement)
    {
        $data = $request->validate(['name' => 'required|string']);
        $agreement->update($data);

        return $this->success($agreement, 'Agreement actualizado correctamente');
    }

    public function patch(Request $request, Agreement $agreement)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $agreement->update($data);

        return $this->success($agreement, 'Agreement parcialmente actualizado');
    }

    public function destroy(Agreement $agreement)
    {
        $agreement->delete();
        return $this->success(null, 'Agreement eliminado correctamente', 204);
    }
}
