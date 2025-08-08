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

    public function officeByUser(Request $request)
    {
        /* 1️⃣  Leer user_id (puede no venir) */
        $userId = $request->query('user_id');          // string|null
        $isNumeric = is_numeric($userId);
        if (!$isNumeric) { $userId = null; }

        /* 2️⃣  Expresiones comunes */
        $orderExpr = $userId
            ? "(consultant_id = {$userId}) DESC, id ASC"
            : "id ASC";                                // sin prioridad si no hay user

        $twoMonthsAgo = Carbon::now()->subMonths(2);

        /* 3️⃣  Casos pendientes de acción (Visita pendiente / en proceso) */
        $pendingActionCases = CaseEntity::query()
            ->where('visit_status', '!=', 'realizado')               // pendiente o en proceso
            ->orderByRaw($orderExpr)
            ->get();

        /* 4️⃣  Casos visitados recientemente (Visita realizada ≤ 2 meses) */
        $recentlyVisitedCases = CaseEntity::query()
            ->where('visit_status', 'realizado')
            ->whereDate('document_signing_date', '>=', $twoMonthsAgo)
            ->orderByRaw($orderExpr)
            ->get();

        /* 5️⃣  Respuesta estándar */
        return $this->success(
            [
                'pendingActionCases'   => $pendingActionCases,
                'recentlyVisitedCases' => $recentlyVisitedCases,
            ],
            'Listado de casos (pendientes y visitados) para oficina'
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
        // Lee el user_id del body (o de un header si lo prefieres)
        $userId = $req->input('user_id', null);

        $updated = app(CaseTransitionService::class)
            ->transition(
                $case,
                $req->input('next_state'),
                $req->input('comments'),
                $userId               // ◀ aquí pasas el user_id
            );

        return $this->success($updated, 'Transición realizada satisfactoriamente');
    }


    /**
     * Endpoint: estadísticas globales de casos
     */
    public function stats(): \Illuminate\Http\JsonResponse
    {
        $now                = Carbon::now();
        $startCurrentMonth  = $now->copy()->startOfMonth();
        $startLastThirty    = $now->copy()->subDays(30);
        $prevMonth          = $now->copy()->subMonth();
        $startPreviousMonth = $prevMonth->copy()->startOfMonth();
        $endPreviousMonth   = $prevMonth->copy()->endOfMonth();
        $todayDay           = $now->day;

        $metrics = [
            'cases_created_current_month'              => CaseEntity::whereBetween('created_at', [$startCurrentMonth, $now])->count(),
            'cases_created_last_thirty_days'           => CaseEntity::where('created_at', '>=', $startLastThirty)->count(),
            'cases_created_previous_month'             => CaseEntity::whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])->count(),
            'cases_created_to_date_current_month'      => CaseEntity::whereBetween('created_at', [$startCurrentMonth, $now])->count(),
            'cases_created_to_date_previous_month'     => CaseEntity::whereBetween('created_at', [$startPreviousMonth, $prevMonth->copy()->day($todayDay)])->count(),
            'closed_cases'                             => CaseEntity::where('overall_status', 'cerrado')->count(),
            'cases_paid_in_collection'                 => CaseEntity::where('state', 'like', '%Recaudacion%')
                                                                  ->where('payment_status', 'pagado')
                                                                  ->count(),
            'cases_in_closing_steps'                   => CaseEntity::where(function($q) {
                                                                foreach (['Cancelado','Desistido','DesistidoSinVisita'] as $step) {
                                                                    $q->orWhere('state','like', "%{$step}%");
                                                                }
                                                            })->count(),
            'cases_pending_collection'                 => CaseEntity::where('state', 'like', '%Recaudacion%')
                                                                  ->where('payment_status', '!=', 'pagado')
                                                                  ->count(),
        ];

        return $this->success(
            [$metrics],
            'Estadisticas para métricas de casos'
        );
    }


}

