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
use Modules\Core\app\Helpers\ColumnMap;


class CaseController extends BaseApiController
{

    public function index()
    {
        $cases = CaseDetail::all();
        $rows = CaseEntityResource::collection($cases)->toArray(request());

        return $this->success(ColumnMap::renameCollection($rows, 'cases'), 'Listado de casos', 200);
    }

    public function show(CaseEntity $case)
    {
        $detail = CaseDetail::find($case->id);
        if (! $detail) {
            return $this->error('Caso no encontrado', 404);
        }
        return $this->success(ColumnMap::renameKeys($detail->toArray(), 'cases'), 'Caso obtenido correctamente', 200);
    }

    public function store(StoreCaseRequest $request)
    {
        $data = $request->validated();

        $data['created_by'] = $data['created_by']
            ?? $request->header('X-User-Id')
            ?? auth()->id();

        $case = CaseEntity::create($data);

        return $this->success($case, 'Caso creado correctamente', 201);
    }

    public function update(UpdateCaseRequest $request, CaseEntity $case)
    {
        $case->fill($request->validated())->save();
        return $this->success($case->refresh(), 'Caso actualizado correctamente', 200);
    }

    public function patch(UpdateCaseRequest $request, CaseEntity $case)
    {
        $case->update($request->validated());
        return $this->success($case, 'Caso actualizado correctamente', 200);
    }

    public function destroy(CaseEntity $case)
    {
        // Borra los registros en la BD de traro por el id del caso OBI
        DB::connection('traro_db')
            ->table('case_flows')
            ->where('obi_case_id', $case->id)
            ->delete();

        $case->delete();

        return $this->success(null, 'Caso eliminado exitosamente', 200);
    }

                            //Endpoints para lógica de negocio de TRARO

    //Trae los casos asociados a los clientes a los cuales está asignado el ID del ejecutivo
    public function recentByAgent(TraroUser $agent)
    {
        $cases = CaseDetail::query()
            ->where('agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->get();

        if ($cases->isEmpty()) {
            return $this->success([], "No se encontraron casos para el ejecutivo/a '{$agent->name}'.", 200);
        }

        return $this->success( ColumnMap::renameCollection($cases->toArray(), 'cases'), "Casos asignados al ejecutivo/a '{$agent->name}'.", 200);
    }

    public function byCustomer(Customer $customer)
    {
        $cases = CaseDetail::where('customer_id', $customer->id)
                           ->orderByDesc('created_at')
                           ->get();

        if ($cases->isEmpty()) {
            return $this->success([], "El cliente «{$customer->name} {$customer->lastname}» no tiene casos registrados", 200);
        }

        return $this->success(ColumnMap::renameCollection($cases->toArray(), 'cases'), "Casos del cliente «{$customer->name} {$customer->lastname}»", 200);
    }

       public function officeByUser(TraroUser $user)
    {
        // 1) Ya viene bindeado
        $userId = (int) $user->id;
        $roleId = (int) $user->role_id;

        // 2) Conexión de configuraciones
        $configConn = (new Configuration)->getConnectionName() ?: config('database.default');

        // 3) User_responsabilities → pasos visibles SOLO por configuración
        $resTypeId = DB::connection($configConn)
            ->table('types')
            ->where('name', 'User_responsabilities')
            ->value('id');

        if (! $resTypeId) {
            return $this->error("No existe el type 'User_responsabilities'.", 422);
        }

        $resCfg = Configuration::where('type_id', $resTypeId)->first();
        $map    = $resCfg?->content ?? [];

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

            if (in_array($userId, $ids, true)) {
                $visibleSteps[] = $step;
            }
        }
        $visibleSteps = array_values(array_unique($visibleSteps));

        $twoMonthsAgo = Carbon::now()->subMonthsNoOverflow(2)->toDateString();
        $base = DB::connection('cases_db')->table('v_cases_details');

        $typeId = DB::connection($configConn)
            ->table('types')
            ->where('name','Columns_by_rol')
            ->value('id');

        if (! $typeId) {
            return $this->error("No existe el type 'Columns_by_rol'.", 422);
        }

        $cfg  = Configuration::where('type_id', $typeId)->first();
        $cont = $cfg?->content ?? [];

        $colsRole3 = (isset($cont['3']) && is_array($cont['3'])) ? $cont['3'] : [];
        $colsRole4 = (isset($cont['4']) && is_array($cont['4'])) ? $cont['4'] : [];
        $colsRole5 = (isset($cont['5']) && is_array($cont['5'])) ? $cont['5'] : [];

        $roleColsByStep = [
            'Denuncio'     => $colsRole4,
            'Programación' => $colsRole3,
            'Visita'       => $colsRole5,
            'Presupuesto'  => $colsRole5,
            'Liquidación'  => $colsRole5,
            'Recaudación'  => $colsRole4,
        ];

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
            'Visita'       => ['visit_status','document_signing_date'],
            'Presupuesto'  => ['budget_status','budget_sending_date'],
            'Liquidación'  => ['settlement_report_date'],
            'Recaudación'  => ['payment_status','collection_date','online_collection_date'],
        ];

        $project = function ($rows, array $roleCols, array $forcedCols) {
        $cols = array_values(array_unique(array_merge($roleCols, $forcedCols)));

        $plain = collect($rows)->map(function ($row) use ($cols) {
            $rec = ['id' => $row->id]; // siempre incluye ID
            foreach ($cols as $c) {
                $rec[$c] = property_exists($row, $c) ? $row->{$c} : null;
            }
            return $rec;
        })->values()->toArray();
      
        return ColumnMap::renameCollection($plain, 'cases');
    };

        $offices = [];

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

        if (in_array('Visita', $visibleSteps, true)) {
            $cols = $roleColsByStep['Visita'];

            $qPend = (clone $base)->whereIn('visit_status', ['pendiente','en proceso']);
            $qRes  = (clone $base)->where('visit_status','realizado')
                                  ->whereDate('document_signing_date','>=',$twoMonthsAgo);

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

        return $this->success(['offices' => $offices], 'Oficina: casos por paso (pendientes y resueltos)', 200);
    }

    //Devuelve arrays next / prev para habilitar botones
    public function transitions(CaseEntity $case)
    {
        $order = config('modules.Cases.CaseEntity_states.states');
        $map   = config('modules.Cases.CaseEntity_states.transitions');

        $currentFqn  = $case->state::class;
        $currentBase = class_basename($currentFqn);
        $idxCurrent  = array_search($currentBase, $order, true);

        $allowedFqn = $map[$currentFqn] ?? [];
        $allowed    = array_map('class_basename', $allowedFqn);

        $next = [];
        $prev = [];
        foreach ($allowed as $state) {
            $idx = array_search($state, $order, true);
            if ($idx === false) continue;
            if ($idx > $idxCurrent)      $next[] = $state;
            elseif ($idx < $idxCurrent)  $prev[] = $state;
        }

        return $this->success(['next' => $next, 'prev' => $prev], 'Transiciones disponibles', 200);
    }

   public function transition(TransitionCaseRequest $req, CaseEntity $case)
    {
        try {
            $updated = app(\Modules\Cases\app\Services\CaseTransitionService::class)->transition(
                $case,
                $req->input('next_state'),
                $req->input('comments'),
                $req->input('user_id')
            );

            return $this->success($updated, 'Transición realizada satisfactoriamente', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Datos inválidos', 422);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage() ?: 'Error interno', 500);
        }
    }

    //Endpoint: estadísticas globales de casos
    public function stats(): \Illuminate\Http\JsonResponse
    {
        $now                = Carbon::now();
        $startCurrentMonth  = $now->copy()->startOfMonth();
        $startLastThirty    = $now->copy()->subDays(30);
        $prevMonth          = $now->copy()->subMonth();
        $startPreviousMonth = $prevMonth->copy()->startOfMonth();
        $endPreviousMonth   = $prevMonth->copy()->endOfMonth();
        $todayDay           = $now->day;
        $prevMonthSameDay   = $prevMonth->copy()->day(min($todayDay, $prevMonth->daysInMonth));

        $metrics = [
            // Casos ingresados
            'cases_created_current_month'           => CaseEntity::whereBetween('created_at', [$startCurrentMonth, $now])->count(),
            'cases_created_last_thirty_days'        => CaseEntity::where('created_at', '>=', $startLastThirty)->count(),
            'cases_created_previous_month'          => CaseEntity::whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])->count(),
            'cases_created_to_date_previous_month'  => CaseEntity::whereBetween('created_at', [$startPreviousMonth, $prevMonthSameDay])->count(),

            // Casos cerrados (mes actual)
            'closed_cases' => CaseEntity::where('overall_status', 'cerrado')
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->count(),

            // Casos cobrados en recaudación (mes actual)
            'cases_paid_in_collection' => CaseEntity::where(function ($q) {
                    $q->where('state', 'like', '%Recaudacion%')
                      ->orWhere('state', 'like', '%Recaudación%');
                })
                ->where('payment_status', 'pagado')
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->count(),

            // Casos en pasos fulminantes NO cerrados (mes actual)
           'cases_in_closing_steps' => CaseEntity::where(function ($q) {
        foreach (['Cancelado','Desistido','DesistidoSinVisita'] as $step) {
            $q->orWhere('state', 'like', "%{$step}%");
                }
                })
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->count(),

            // Casos en recaudación pendientes de pago (mes actual)
            'cases_pending_collection' => CaseEntity::where(function ($q) {
                    $q->where('state', 'like', '%Recaudacion%')
                      ->orWhere('state', 'like', '%Recaudación%');
                })
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                      ->orWhere('payment_status', '!=', 'pagado');
                })
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->count(),
        ];

        return $this->success($metrics, 'Estadísticas para métricas de casos', 200);
    }
}

