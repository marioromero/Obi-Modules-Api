<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Cases\app\Http\Requests\StoreCaseRequest;
use Modules\Cases\app\Http\Requests\UpdateCaseRequest;
use Modules\Cases\app\Resources\CaseEntityResource;
use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;
use Carbon\Carbon;
use Modules\Users\Models\User;

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

    //Endpoints para lógica de negocio de TRARO

        public function recentByAgent($agentId)
        {
            // 1) Verificar que el agente existe
            $agent = User::find($agentId);
            if (! $agent) {
                return $this->error(
                    "No existe ningún ejecutivo/a con ID {$agentId}",
                    404
                );
            }

            // 2) Filtrar casos de los últimos 6 meses
            $sixMonthsAgo = Carbon::now()->subMonths(6);
            $cases = CaseEntity::where('agent_id', $agentId)
                ->where('created_at', '>=', $sixMonthsAgo)
                ->get();

            // 3) Si no tiene casos, devolver mensaje descriptivo
            if ($cases->isEmpty()) {
                return $this->success(
                    [],
                    "No se encontraron casos para el ejecutivo/a '{$agent->name}' en los últimos 6 meses"
                );
            }

            // 4) Caso contrario, devolver la lista
            return $this->success(
                $cases,
                "Casos de los últimos 6 meses para el ejecutivo/a '{$agent->name}'"
            );
        }
}

