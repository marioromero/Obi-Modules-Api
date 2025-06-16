<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Cases\app\Http\Requests\StoreCaseRequest;
use Modules\Core\App\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;


class CaseController extends BaseApiController
{

    public function index()
    {
        $paginator = CaseEntity::paginate(15);
        return $this->paginated($paginator, 'Listado de cases');
    }

    public function show(CaseEntity $case)
    {
        return $this->success($case, 'Case obtenido correctamente');
    }

    public function store(StoreCaseRequest $request)
    {
        // El FormRequest ya hizo la validación y devuelve solo campos permitidos
        $case = CaseEntity::create($request->validated());

        return $this->success($case, 'Case creado correctamente', 201);
    }

    public function update(Request $request, CaseEntity $case)
    {
        $data = $request->validate(['name' => 'required|string']);
        $case->update($data);

        return $this->success($case, 'Case actualizado correctamente');
    }

    public function patch(Request $request, CaseEntity $case)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $case->update($data);

        return $this->success($case, 'Case parcialmente actualizado');
    }

    public function destroy(CaseEntity $case)
    {
        $case->delete();
        return $this->success(null, 'Case eliminado correctamente', 204);
    }
}

