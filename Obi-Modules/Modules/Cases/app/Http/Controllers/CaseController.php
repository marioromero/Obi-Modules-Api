<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Cases\app\Http\Requests\StoreCaseRequest;
use Modules\Cases\app\Http\Requests\UpdateCaseRequest;
use Modules\Cases\app\Resources\CaseEntityResource;
use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;


class CaseController extends BaseApiController
{

   public function index()
    {
        $cases = CaseEntity::all();

        // Envuelve cada modelo en el Resource
        $collection = CaseEntityResource::collection($cases);

        return $this->success($collection, 'Listado de casos');
    }

    public function show(CaseEntity $case)
    {
        return $this->success($case, 'Caso obtenido correctamente');
    }

    public function store(StoreCaseRequest $request)
    {
        // El FormRequest ya hizo la validación y devuelve solo campos permitidos
        $case = CaseEntity::create($request->validated());

        return $this->success($case, 'Caso creado correctamente', 201);
    }

    public function update(Request $request, CaseEntity $case)
    {
        $data = $request->validate(['name' => 'required|string']);
        $case->update($data);

        return $this->success($case, 'Caso actualizado correctamente');
    }

    public function patch(UpdateCaseRequest $request, CaseEntity $case)
    {
        $case->update($request->validated());

        return $this->success($case, 'Caso actualizado correctamente');
    }

    public function destroy(CaseEntity $case)
    {
        $case->delete();
         return $this->success(null,'Caso eliminado exitosamente',200);
    }
}

