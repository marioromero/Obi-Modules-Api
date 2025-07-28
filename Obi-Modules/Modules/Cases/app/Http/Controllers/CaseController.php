<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Cases\app\Http\Requests\StoreCaseRequest;
use Modules\Cases\app\Http\Requests\TransitionCaseRequest;
use Modules\Cases\app\Http\Requests\UpdateCaseRequest;
use Modules\Cases\app\Resources\CaseEntityResource;
use Modules\Cases\app\Services\CaseTransitionService;
use Modules\Cases\Models\CaseDetail;
use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Customers\Models\Customer;
use Modules\Users\Models\User;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;


class CaseController extends BaseApiController
{

        public function index()
    {

        $cases = CaseDetail::all();
        $collection = CaseEntityResource::collection($cases);

        return $this->success($collection, 'Listado de casos');
    }


    public function show(int $id)
    {
        $case = CaseDetail::findOrFail($id);

        return $this->success($case, 'Caso obtenido correctamente');
    }

    public function store(StoreCaseRequest $request)
    {
        // 1) Traes los campos validados
        $data = $request->validated();

        // 2) Inyectas el ID del usuario que hace la llamada
        //    a) Si la ruta está protegida con auth()  ➜ auth()->id()
        //    b) Si Traro envía un header X-User-Id    ➜ header()
        $data['created_by'] = $data['created_by']        // ① JSON
                   ?? $request->header('X-User-Id') // ② Encabezado
                   ?? auth()->id();                // ③ Sesión

        // 3) Creas el caso
        $case = CaseEntity::create($data);

        // 4) Respuesta
        return $this->success($case, 'Caso creado correctamente', 201);
    }

    public function update(UpdateCaseRequest $request, int $id)
        {
            // 1) Buscar el caso o lanzar 404
            $case = CaseEntity::findOrFail($id);          // ← conexión cases_db

            // 2) Cargar solo los campos validados
            $case->fill($request->validated());

            // 3) Guardar cambios
            $case->save();

            // 4) Devolver el modelo actualizado
            return $this->success($case->refresh(), 'Caso actualizado correctamente');
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

    public function recentByAgent(int $agentId)
    {
        /* 1) Verificar que el agente exista en la BD de Traro  */
        $agent = User::on('traro_db')           // ← usa la conexión traro_db
                     ->where('role_id', 2)      //   ejecutivos / captadores
                     ->find($agentId);

        if (! $agent) {
            return $this->error(
                "No existe ningún ejecutivo/a con ID {$agentId}",
                404
            );
        }

        /* 2) Casos de los últimos 6 meses (vista v_cases_details) */
        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $cases = CaseDetail::where('agent_id', $agentId)
                           ->where('created_at', '>=', $sixMonthsAgo)
                           ->orderByDesc('created_at')
                           ->get();

        /* 3) Sin resultados */
        if ($cases->isEmpty()) {
            return $this->success(
                [],
                "No se encontraron casos para el ejecutivo/a '{$agent->name}' en los últimos 6 meses"
            );
        }

        /* 4) Respuesta con datos enriquecidos */
        return $this->success(
            $cases,
            "Casos de los últimos 6 meses para el ejecutivo/a '{$agent->name}'"
        );
    }

    public function byCustomer(int $customerId)
        {
            /* 1) Verificar que el cliente exista                      */
            $customer = Customer::find($customerId);
            if (! $customer) {
                return $this->error(
                    "No existe ningún cliente con ID {$customerId}",
                    404
                );
            }

            /* 2) Traer los casos desde la vista v_cases_details       */
            $cases = CaseDetail::where('customer_id', $customerId)
                               ->orderByDesc('created_at')
                               ->get();

            /* 3) Si no hay casos, devolver arreglo vacío con mensaje  */
            if ($cases->isEmpty()) {
                return $this->success(
                    [],
                    "El cliente «{$customer->name} {$customer->lastname}» no tiene casos registrados"
                );
            }

            /* 4) Respuesta con casos enriquecidos                     */
            return $this->success(
                $cases,
                "Casos del cliente «{$customer->name} {$customer->lastname}»"
            );
        }

/** Devuelve arrays next / prev para habilitar botones */
public function transitions(CaseEntity $case)
{
    /* ---------------- Configuración de la máquina ---------------- */
    $order    = config('modules.Cases.CaseEntity_states.states');        // lista ordenada
    $map      = config('modules.Cases.CaseEntity_states.transitions');   // matriz FQN → FQN[]

    /* ---------------- Estado actual ------------------------------ */
    $currentFqn  = $case->state::class;
    $currentBase = class_basename($currentFqn);
    $idxCurrent  = array_search($currentBase, $order, true);

    /* ---------------- Destinos permitidos ------------------------ */
    $allowedFqn = $map[$currentFqn] ?? [];
    $allowed    = array_map('class_basename', $allowedFqn);              // basenames

    /* ---------------- Clasificar en next / prev ------------------ */
    $next = [];
    $prev = [];
    foreach ($allowed as $state) {
        $idx = array_search($state, $order, true);
        if ($idx === false) {
            continue; // estado no listado en 'states'
        }
        if ($idx > $idxCurrent) {
            $next[] = $state;          // va hacia adelante
        } elseif ($idx < $idxCurrent) {
            $prev[] = $state;          // va hacia atrás
        }
    }

    return $this->success(['next' => $next, 'prev' => $prev]);
}

    /* =============================================================== *
     *              NUEVO  ▸  Ejecutar transición                      *
     * =============================================================== */
    public function transition(TransitionCaseRequest $req, CaseEntity $case)
    {
        $updated = app(CaseTransitionService::class)->transition(
            $case,
            $req->input('next_state'),
            $req->input('comments')
        );

        return $this->success($updated, 'Transición realizada satisfactoriamente');
    }
}

