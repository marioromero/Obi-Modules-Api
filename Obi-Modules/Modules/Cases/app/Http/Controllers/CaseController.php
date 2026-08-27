<?php

namespace Modules\Cases\app\Http\Controllers;

use Modules\Cases\app\Http\Requests\StoreCaseRequest;
use Modules\Cases\app\Http\Requests\TransitionCaseRequest;
use Modules\Cases\app\Http\Requests\UpdateCaseByCodeRequest;
use Modules\Cases\app\Http\Requests\UpdateCaseRequest;
use Modules\Cases\app\Resources\CaseEntityResource;
use Modules\Cases\app\Services\CaseTransitionService;
use Modules\Cases\Models\CaseDetail;
use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;
use Carbon\Carbon;
use Modules\Cases\Support\CasesCache;
use Illuminate\Validation\ValidationException;
use Modules\Configurations\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Modules\Users\Models\TraroUser;
use Illuminate\Support\Facades\Log;
use Modules\Customers\Models\Customer;
use Modules\Users\Models\User;
use Illuminate\Http\Request;
use Modules\Core\app\Helpers\ColumnMap;
use Modules\Cases\Models\CaseEntityStepLog;
use Modules\Cases\Models\Comment;
use Modules\Schedules\Models\Schedule;
use Illuminate\Support\Facades\Storage;


class CaseController extends BaseApiController
{

    public function index()
    {
        $cases = CaseDetail::all();
        $collection = CaseEntityResource::collection($cases);
        return $this->success($collection, 'Listado de casos', 200);
    }

    public function show(CaseEntity $case)
    {
        $tStart = microtime(true);

        $detail = CaseDetail::find($case->id);
        if (! $detail) {
            return $this->error('Caso no encontrado', 404);
        }

        $response = $this->success($detail, 'Caso obtenido correctamente', 200);
        $ttfbMs = (microtime(true) - $tStart) * 1000;

        $response->headers->set('X-TTFB-ms', (string) round($ttfbMs, 2));
        $response->headers->set('X-Case-Id', (string) $case->id);

        if ($ttfbMs > 2000) {
            Log::channel('transitions')->warning('cases.show.slow_ttfb', [
                'case_id' => $case->id,
                'ttfb_ms' => round($ttfbMs, 2),
                'timestamp' => date('c'),
            ]);
        }

        return $response;
    }

    public function store(StoreCaseRequest $request)
    {
        $data = $request->validated();

        $userId = $data['created_by']
            ?? $request->header('X-User-Id')
            ?? $request->input('user_id')
            ?? ($request->user() ? $request->user()->id : null);

        $data['created_by'] = $userId;

        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);

            if ($customer && empty($customer->assigned_agent)) {
                if (! $userId) {
                    throw ValidationException::withMessages([
                        'created_by' => 'No se pudo determinar el usuario que está ingresando el caso para asignarlo como ejecutivo del cliente.',
                    ]);
                }

                $customer->assigned_agent = (int) $userId;
                $customer->save();
            }
        }

        $case = CaseEntity::create($data);

        return $this->success($case, 'Caso creado correctamente', 201);
    }

    public function update(UpdateCaseRequest $request, CaseEntity $case)
    {
        $data = $request->validated();

        if (isset($data['amount_paid']) && (int) $data['amount_paid'] > 0) {
            $data['probable_payment_date'] = now()->toDateString();
        }

        $case->fill($data)->save();
        return $this->success($case->refresh(), 'Caso actualizado correctamente', 200);
    }

   public function patch(UpdateCaseRequest $request, CaseEntity $case)
     {
        $data = $request->validated();

        if (isset($data['amount_paid']) && (int) $data['amount_paid'] > 0) {
            $data['probable_payment_date'] = now()->toDateString();
        }

        $case->fill($data)->save();

        return $this->success($case->refresh(), 'Caso actualizado correctamente', 200);
    }

    public function destroy(CaseEntity $case)
    {
        $caseId = $case->id;
        $caseCode = $case->code;

        DB::connection('traro_db')
            ->table('case_flows')
            ->where('obi_case_id', $caseId)
            ->delete();

        CaseEntityStepLog::where('case_id', $caseId)->delete();

        Comment::where('case_id', $caseId)->delete();

        Schedule::where('case_id', $caseId)->delete();

        if ($caseCode) {
            try {
                $disk = Storage::disk('cases-docs');
                if ($disk->exists($caseCode)) {
                    $disk->deleteDirectory($caseCode);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudieron eliminar documentos del caso', [
                    'case_id' => $caseId,
                    'code' => $caseCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        CasesCache::syncOne($caseId);

        $case->delete();

        return $this->success(null, 'Caso eliminado exitosamente', 200);
    }

    public function softDelete(CaseEntity $case)
    {
        $case->softdeleted = $case->softdeleted ? 0 : 1;
        $case->updated_at  = now();

        $case->save();

        // 🔁 Sincronizar inmediatamente el cache para este caso
        CasesCache::syncOne($case->id);

        return $this->success($case, 'Caso actualizado (softdeleted toggled).', 200);
    }

    public function refreshCache(int $caseId)
   {
    try {
        \Modules\Cases\Support\CasesCache::syncOne($caseId);

        return $this->success(
            ['case_id' => $caseId],
            'Cache del caso actualizado correctamente'
        );

    } catch (\Throwable $e) {
        return $this->error(
            'No se pudo refrescar el cache del caso: ' . $e->getMessage(),
            500
        );
    }
}

                            //Endpoints para lógica de negocio de TRARO

    //Trae los casos asociados a los clientes a los cuales está asignado el ID del ejecutivo
    public function recentByAgent(TraroUser $agent)
    {
        $cases = CaseDetail::query()
            ->where('agent_id', $agent->id)
            ->orderByDesc('created_at')
            ->get()
            ->unique('id')
            ->values();

        $columnsEn = [];
        try {
            $columnsEn = DB::connection('cases_db')
                ->getSchemaBuilder()
                ->getColumnListing('v_cases_details');
        } catch (\Throwable $e) {
            $columnsEn = [];
        }

        if (empty($columnsEn)) {
            try {
                $dbName = (string) config('database.connections.cases_db.database');
                $rows = DB::connection('cases_db')->select(
                    "SELECT COLUMN_NAME
                       FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                   ORDER BY ORDINAL_POSITION",
                    [$dbName, 'v_cases_details']
                );
                $columnsEn = array_map(fn ($r) => $r->COLUMN_NAME, $rows);
            } catch (\Throwable $e) {
                if ($cases->isNotEmpty()) {
                    $columnsEn = array_keys($cases->first()->toArray());
                } else {
                    $columnsEn = [];
                }
            }
        }

        $hidden = ['sent_to_acepta'];
        $columnsEn = array_values(array_diff($columnsEn, $hidden));

        //Columnas solicitadas por gente de traro
        $columnsEn = [
            'id',
            'code',
            'customer_name',
            'customer_id',
            'created_at',
            'document_signing_date',
            'fecha_firma_contrato',
            'fecha_firma_mandato',
            'state',
            'commune_name',
            'accident_type_name',
            'bank_name',
            'settlement_report_date',
            'collection_date',
            'approved_amount',
            'case_flow_last',
        ];

        $columnsEs = ColumnMap::translate($columnsEn, 'cases');

        // Reducimos cada caso solo a esas columnas
        $cases = $cases->map(fn ($row) => collect($row)->only($columnsEn));

        return $this->success(
            [
                'columns' => $columnsEs,
                'cases'   => $cases,
            ],
            "Casos asignados al ejecutivo/a '{$agent->name}'.",
            200
        );
    }

    public function byCustomer(Customer $customer)
    {
        $cases = CaseDetail::where('customer_id', $customer->id)
                           ->orderByDesc('created_at')
                           ->get();

        if ($cases->isEmpty()) {
            return $this->success([], "El cliente «{$customer->name} {$customer->lastname}» no tiene casos registrados", 200);
        }

        return $this->success($cases, "Casos del cliente «{$customer->name} {$customer->lastname}»", 200);
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
        return collect($rows)->map(function ($row) use ($cols) {
            $rec = ['id' => $row->id];
            foreach ($cols as $c) {
                $rec[$c] = property_exists($row, $c) ? $row->{$c} : null;
            }
            return $rec;
        })->values();
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
        $order = config('cases.CaseEntity_states.states');
        $map   = config('cases.CaseEntity_states.transitions');

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
         $tStart = microtime(true);

         try {
             // Estado origen antes de transicionar (Clase base: Ingreso, Denuncio, ...)
             $fromBase   = class_basename($case->state::class);
             $nextState  = $req->input('next_state');
             $toBase     = class_basename(
                 str_contains($nextState, '\\')
                     ? $nextState
                     : "Modules\\Cases\\States\\Traro\\{$nextState}"
             );

             $updated = app(\Modules\Cases\app\Services\CaseTransitionService::class)->transition(
                 $case,
                 $nextState,
                 $req->input('comments'),
                 $req->input('user_id')
             );

             // Sólo se rellenan los flags de firma en case_flows para la transición
             // manual Ingreso -> Denuncio. Para cualquier otra transición se deja sin
             // firmar case_flows; la firma la debe registrar el cron de etapas-1
             // consultando Acepta (única fuente de verdad del estado de firma real).
             if ($fromBase === 'Ingreso' && $toBase === 'Denuncio') {
                 $caseFlow = DB::connection('traro_db')
                           ->table('case_flows')
                           ->where('obi_case_id', $case->id)
                           ->where('is_active', 1)
                           ->first();

                 if ($caseFlow) {
                     $updates = [];
                     $needsUpdate = false;

                     if (!isset($caseFlow->mandato_firmado) || $caseFlow->mandato_firmado == 0) {
                         $updates['mandato_firmado']    = 1;
                         $updates['fecha_firma_mandato'] = now();
                         $needsUpdate = true;
                     }

                     if (!isset($caseFlow->contrato_firmado) || $caseFlow->contrato_firmado == 0) {
                         $updates['contrato_firmado']    = 1;
                         $updates['fecha_firma_contrato'] = now();
                         $needsUpdate = true;
                     }

                     if ($needsUpdate) {
                         DB::connection('traro_db')
                           ->table('case_flows')
                           ->where('obi_case_id', $case->id)
                           ->where('is_active', 1)
                           ->update($updates);

                         $case->update([
                             'document_signing_date' => now()->toDateString()
                         ]);
                     }
                 }
             }

             $response = $this->success($updated, 'Transición realizada satisfactoriamente', 200);
             $ttfbMs = (microtime(true) - $tStart) * 1000;

             $response->headers->set('X-TTFB-ms', (string) round($ttfbMs, 2));
             $response->headers->set('X-Case-Id', (string) $case->id);

             if ($ttfbMs > 2000) {
                 Log::channel('transitions')->warning('cases.transition.slow_ttfb', [
                     'case_id' => $case->id,
                     'from_state' => $fromBase,
                     'to_state' => $toBase,
                     'ttfb_ms' => round($ttfbMs, 2),
                     'timestamp' => date('c'),
                 ]);
             }

             return $response;
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

    //metodo que trae el historial de comentarios de un caso x id
    public function getCommentsByCaseId(CaseEntity $case)
    {
        $desc = $case->description;

        if (is_string($desc)) {
            $decoded = json_decode($desc, true);
            $desc = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($desc)) {
            $desc = [];
        }

        $comments = $desc['comments'] ?? [];
        if (!is_array($comments)) {
            $comments = [];
        }

        return $this->success(
            ['comments' => array_values($comments)],
            'Historial de comentarios obtenido',
            200
        );
    }

    //metodo para guardar comentarios de caso x id
    public function StoreCommentForCase(Request $request, CaseEntity $case)
    {
        $validated = $request->validate([
            'user'    => 'required|string',
            'content' => 'required|string',
        ]);

        $payload  = is_array($case->description) ? $case->description : [];
        $comments = isset($payload['comments']) && is_array($payload['comments']) ? $payload['comments'] : [];

        $comments[] = [
            'date'    => now('America/Santiago')->format('d/m/Y H:i:s'),
            'user'    => $validated['user'],
            'content' => $validated['content'],
        ];

        $case->description = ['comments' => array_values($comments)];
        $case->save();

        return $this->success($case->refresh(), 'Comentario agregado correctamente', 201);
    }

    public function UpdateCaseByCode(UpdateCaseByCodeRequest $request, string $code)
    {
        $case = CaseEntity::where('code', $code)->first();

        if (! $case) {
            return $this->error("Caso con código {$code} no encontrado", 404);
        }

        $case->fill($request->validated())->save();

        return $this->success($case->refresh(), "Caso {$code} actualizado correctamente", 200);
    }

    public function getCaseByCode(string $code)
    {
        $case = CaseEntity::where('code', $code)->first();

        if (! $case) {
            return $this->error("Caso con código {$code} no encontrado", 404);
        }

        $detail = CaseDetail::find($case->id);
        if (! $detail) {
            return $this->error('Caso no encontrado', 404);
        }

        return $this->success($detail, 'Caso obtenido correctamente', 200);
    }

    public function filterByPaymentDate(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = CaseEntity::query()->with('customer');

        if ($startDate && $endDate) {
            // Filtrar entre fechas
            $query->whereBetween('probable_payment_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($startDate) {
            // Filtrar por una fecha específica (día completo)
            $query->whereDate('probable_payment_date', Carbon::parse($startDate)->toDateString());
        } elseif ($endDate) {
            // Si solo se pasa end_date, filtrar por ese día
            $query->whereDate('probable_payment_date', Carbon::parse($endDate)->toDateString());
        } else {
            return $this->error('Debe proporcionar al menos una fecha (start_date o end_date)', 400);
        }

        $cases = $query->get()->map(function ($case) {
            $case->customer_name = $case->customer?->name." ".$case->customer?->lastname ?? null;
            $case->customer_email = $case->customer?->email ?? null;
            return $case;
        });

        return $this->success($cases, 'Casos filtrados por fecha de pago', 200);
    }

}

