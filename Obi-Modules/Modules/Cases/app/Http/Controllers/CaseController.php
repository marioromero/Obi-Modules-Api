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
use Illuminate\Validation\ValidationException;
use Modules\Configurations\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Modules\Users\Models\TraroUser;
use Illuminate\Support\Facades\Log;
use Modules\Customers\Models\Customer;
use Modules\Users\Models\User;
use Illuminate\Http\Request;


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
        /* 1) Verificar que el usuario exista (sin amarrar a role_id) */
        $agent = TraroUser::on('traro_db')->find($agentId);

        if (! $agent) {
            return $this->error("No existe ningún usuario con ID {$agentId}", 404);
        }

        /* 2) TODOS los casos de clientes cuyo assigned_agent = $agentId */
        $cases = \Modules\Cases\Models\CaseDetail::query()
            ->where('assigned_agent', $agentId)  // ← ahora filtra por el agente asignado al cliente
            ->orderByDesc('created_at')
            ->get();

        /* 3) Respuestas */
        if ($cases->isEmpty()) {
            return $this->success(
                [],
                "No se encontraron casos para el ejecutivo/a '{$agent->name}'."
            );
        }

        return $this->success(
            $cases,
            "Casos asignados al ejecutivo/a '{$agent->name}'."
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
        /* 1) Validación: user_id requerido y usuario existente */
        $data = $request->validate([
            'user_id' => ['required','integer','min:1'],
        ]);
        $user = TraroUser::find($data['user_id']);
        if (! $user) {
            throw ValidationException::withMessages(['user_id' => 'usuario no encontrado']);
        }
        $userId  = (int) $user->id;
        $roleId  = (int) $user->role_id;

       /* 2) Pasos visibles según rol (base) */
    $stepsByRole = [
        5 => ['Visita','Presupuesto','Liquidación'], // Asesor
        3 => ['Programación'],                       // Coordinador
        4 => ['Denuncio','Recaudación'],            // Administrativo
        // 2 (Ejecutivo) y 1 (Administrador): sin pasos por ahora
    ];
    $visibleSteps = $stepsByRole[$roleId] ?? [];

    /* 2) Pasos visibles basados SOLO en configuraciones (User_responsabilities) */
    $configConn = (new Configuration)->getConnectionName() ?: config('database.default');

    $resTypeId = DB::connection($configConn)
        ->table('types')
        ->where('name', 'User_responsabilities')
        ->value('id');

    if (! $resTypeId) {
        throw ValidationException::withMessages([
            'config' => "No existe el type 'User_responsabilities'.",
        ]);
    }

    $resCfg = Configuration::where('type_id', $resTypeId)->firstOrFail();
    $map   = $resCfg->content ?? [];

    // Normalizar claves del seeder a nombres canónicos (con acentos)
    $aliases = [
        'Denuncio'     => 'Denuncio',
        'Programacion' => 'Programación',
        'Visita'       => 'Visita',
        'Presupuesto'  => 'Presupuesto',
        'Liquidacion'  => 'Liquidación',
        'Recaudacion'  => 'Recaudación',
    ];

    $visibleSteps = [];
    foreach ($map as $rawStep => $data) {
        $step = $aliases[$rawStep] ?? null;
        if (! $step) continue;

        $ids = is_array($data) && isset($data['user_assigned'])
            ? (array) $data['user_assigned']
            : [];
        $ids = array_map('intval', $ids);

        if (in_array((int) $userId, $ids, true)) {
            $visibleSteps[] = $step;
        }
    }
    $visibleSteps = array_values(array_unique($visibleSteps)); // puede quedar vacío y está OK

    /* 3) Ventana de "resueltos" (2 meses) y base */
    $twoMonthsAgo = Carbon::now()->subMonthsNoOverflow(2)->toDateString();
    $base = DB::connection('cases_db')->table('v_cases_details');

    /* 4) Cargar Columns_by_rol para proyección por paso */
    $typeId = DB::connection($configConn)
        ->table('types')
        ->where('name','Columns_by_rol')
        ->value('id');

    if (! $typeId) {
        throw ValidationException::withMessages([
            'config' => "No existe el type 'Columns_by_rol'.",
        ]);
    }

    $cfg  = Configuration::where('type_id', $typeId)->firstOrFail();
    $cont = $cfg->content ?? [];

    /* Columnas por rol (asegúrate de tener definidas 3, 4 y 5 en Columns_by_rol) */
    $colsRole3 = (isset($cont['3']) && is_array($cont['3'])) ? $cont['3'] : []; // Coordinador
    $colsRole4 = (isset($cont['4']) && is_array($cont['4'])) ? $cont['4'] : []; // Administrativo
    $colsRole5 = (isset($cont['5']) && is_array($cont['5'])) ? $cont['5'] : []; // Asesor

    /* Rol base de columnas por paso (no depende del rol del usuario) */
    $roleColsByStep = [
        'Denuncio'     => $colsRole4, // Administrativo
        'Programación' => $colsRole3, // Coordinador
        'Visita'       => $colsRole5, // Asesor
        'Presupuesto'  => $colsRole5, // Asesor
        'Liquidación'  => $colsRole5, // Asesor
        'Recaudación'  => $colsRole4, // Administrativo
    ];

    /* 5) Columnas obligatorias por paso (pendientes / resueltos) */
    $forcePending = [
        'Denuncio'     => ['denounce_status'],
        'Programación' => ['scheduling_status'],
        'Visita'       => ['visit_status'],
        'Presupuesto'  => ['budget_status'],
        'Liquidación'  => [],
        'Recaudación'  => ['payment_status'],
    ];
    $forceResolved = [
        'Denuncio'     => ['denounce_status','complaint_date'],
        'Programación' => ['scheduling_status','inspection_date'],
        // 👇 Cambio aquí: usar document_signing_date en Visita (no inspection_date)
        'Visita'       => ['visit_status','document_signing_date'],
        'Presupuesto'  => ['budget_status','budget_sending_date'],
        'Liquidación'  => ['settlement_report_date'],
        'Recaudación'  => ['payment_status','collection_date','online_collection_date'],
    ];

    // Helper: proyecta id + (cols rol ∪ forzadas)
    $project = function ($rows, array $roleCols, array $forcedCols) {
        $cols = array_values(array_unique(array_merge($roleCols, $forcedCols)));
        return collect($rows)->map(function ($row) use ($cols) {
            $rec = ['id' => $row->id];
            foreach ($cols as $c) {
                $rec[$c] = property_exists($row, $c) ? $row->{$c} : null;
            }
            return $rec;
        })->values();
    };

    $offices = [];

    /* --------- Denuncio --------- */
    if (in_array('Denuncio', $visibleSteps, true)) {
        $cols = $roleColsByStep['Denuncio'];
        $pending = (clone $base)
            ->whereIn('denounce_status', ['pendiente','en proceso'])
            ->orderBy('id','asc')->get();
        $resolved = (clone $base)
            ->where('denounce_status','realizado')
            ->whereDate('complaint_date','>=',$twoMonthsAgo)
            ->orderBy('id','asc')->get();
        $offices['Denuncio'] = [
            'pendingActionCases'    => $project($pending, $cols, $forcePending['Denuncio']),
            'recentlyResolvedCases' => $project($resolved, $cols, $forceResolved['Denuncio']),
        ];
    }

    /* --------- Programación --------- */
    if (in_array('Programación', $visibleSteps, true)) {
        $cols = $roleColsByStep['Programación'];
        $pending = (clone $base)
            ->whereIn('scheduling_status', ['pendiente','en proceso'])
            ->orderBy('id','asc')->get();
        $resolved = (clone $base)
            ->where('scheduling_status','realizado')
            ->whereDate('inspection_date','>=',$twoMonthsAgo)
            ->orderBy('id','asc')->get();
        $offices['Programación'] = [
            'pendingActionCases'    => $project($pending, $cols, $forcePending['Programación']),
            'recentlyResolvedCases' => $project($resolved, $cols, $forceResolved['Programación']),
        ];
    }

    /* --------- Visita (cambio: usa document_signing_date en resueltos) --------- */
    if (in_array('Visita', $visibleSteps, true)) {
        $cols = $roleColsByStep['Visita'];

        $qPend = (clone $base)->whereIn('visit_status', ['pendiente','en proceso']);
        $qRes  = (clone $base)->where('visit_status','realizado')
                              ->whereDate('document_signing_date','>=',$twoMonthsAgo); // ← cambio

        if ($userId) {
            $qPend->orderByRaw('(consultant_id = ?) DESC, id ASC', [$userId]);
            $qRes ->orderByRaw('(consultant_id = ?) DESC, id ASC', [$userId]);
        } else {
            $qPend->orderBy('id','asc');
            $qRes ->orderBy('id','asc');
        }

        $offices['Visita'] = [
            'pendingActionCases'    => $project($qPend->get(), $cols, $forcePending['Visita']),
            'recentlyResolvedCases' => $project($qRes->get(),  $cols, $forceResolved['Visita']),
        ];
    }

    /* --------- Presupuesto --------- */
    if (in_array('Presupuesto', $visibleSteps, true)) {
        $cols = $roleColsByStep['Presupuesto'];
        $pending = (clone $base)
            ->whereIn('budget_status', ['pendiente','en proceso'])
            ->orderBy('id','asc')->get();
        $resolved = (clone $base)
            ->where('budget_status','realizado')
            ->whereDate('budget_sending_date','>=',$twoMonthsAgo)
            ->orderBy('id','asc')->get();
        $offices['Presupuesto'] = [
            'pendingActionCases'    => $project($pending, $cols, $forcePending['Presupuesto']),
            'recentlyResolvedCases' => $project($resolved, $cols, $forceResolved['Presupuesto']),
        ];
    }

    /* --------- Liquidación --------- */
    if (in_array('Liquidación', $visibleSteps, true)) {
        $cols = $roleColsByStep['Liquidación'];
        $pending = (clone $base)
            ->whereNull('settlement_report_date')
            ->orderBy('id','asc')->get();
        $resolved = (clone $base)
            ->whereNotNull('settlement_report_date')
            ->whereDate('settlement_report_date','>=',$twoMonthsAgo)
            ->orderBy('id','asc')->get();
        $offices['Liquidación'] = [
            'pendingActionCases'    => $project($pending, $cols, $forcePending['Liquidación']),
            'recentlyResolvedCases' => $project($resolved, $cols, $forceResolved['Liquidación']),
        ];
    }

    /* --------- Recaudación --------- */
    if (in_array('Recaudación', $visibleSteps, true)) {
        $cols = $roleColsByStep['Recaudación'];
        $pending = (clone $base)
            ->whereIn('payment_status', ['pendiente','cobranza','parcialmente pagado'])
            ->orderBy('id','asc')->get();
        $resolved = (clone $base)
            ->where(function ($q) use ($twoMonthsAgo) {
                $q->where(function ($qq) use ($twoMonthsAgo) {
                    $qq->where('payment_status','pagado')
                       ->whereDate('collection_date','>=',$twoMonthsAgo);
                })->orWhere(function ($qq) use ($twoMonthsAgo) {
                    $qq->where('payment_status','cobranza online')
                       ->whereDate('online_collection_date','>=',$twoMonthsAgo);
                });
            })
            ->orderBy('id','asc')->get();
        $offices['Recaudación'] = [
            'pendingActionCases'    => $project($pending, $cols, $forcePending['Recaudación']),
            'recentlyResolvedCases' => $project($resolved, $cols, $forceResolved['Recaudación']),
        ];
    }

    return $this->success(['offices' => $offices], 'Oficina: casos por paso (pendientes y resueltos)');
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

