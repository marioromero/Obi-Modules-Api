<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\Stats;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Cases\Models\CaseEntity;
use Illuminate\Http\Request;

class StatsController extends BaseApiController
{

    public function index()
    {
        $paginator = Stats::paginate(15);
        return $this->paginated($paginator, 'Listado de stats');
    }

    public function show(Stats $stats)
    {
        return $this->success($stats, 'Stats obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $stats = Stats::create($data);

        return $this->success($stats, 'Stats creado correctamente', 201);
    }

    public function update(Request $request, Stats $stats)
    {
        $data = $request->validate(['name' => 'required|string']);
        $stats->update($data);

        return $this->success($stats, 'Stats actualizado correctamente');
    }

    public function patch(Request $request, Stats $stats)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $stats->update($data);

        return $this->success($stats, 'Stats parcialmente actualizado');
    }

    public function destroy(Stats $stats)
    {
        $stats->delete();
        return $this->success(null, 'Stats eliminado correctamente', 204);
    }

    //Metricas por rol
     public function statsByRole(int $roleId): JsonResponse
    {
        $now                = Carbon::now();
        $startCurrentMonth  = $now->copy()->startOfMonth();
        $startLastThirty    = $now->copy()->subDays(30);
        $prevMonth          = $now->copy()->subMonth();
        $startPreviousMonth = $prevMonth->copy()->startOfMonth();
        $endPreviousMonth   = $prevMonth->copy()->endOfMonth();
        $todayDay           = $now->day;
        $prevMonthSameDay   = $prevMonth->copy()->day(min($todayDay, $prevMonth->daysInMonth));

        // Métricas base para todos los roles
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

            // Casos en pasos fulminantes (NO cerrados) (mes actual)
            'cases_in_closing_steps' => CaseEntity::where(function ($q) {
                    foreach (['Cancelado','Desistido','DesistidoSinVisita'] as $step) {
                        $q->orWhere('state', 'like', "%{$step}%");
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('overall_status')
                      ->orWhere('overall_status', '<>', 'cerrado');
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

        // Extensiones por rol
        switch ($roleId) {
            case 1: // Administrador
                $rangeStart = $this->firstDayMinus12Months();
                $rangeEnd   = $this->firstDayCurrentMonth();

                //Listado de pagos (suma total de todos los montos pagados) – total tabla
                $metrics['total_paid_amount'] = (int) (CaseEntity::sum('amount_paid') ?? 0);

                //Casos ingresados por mes (últimos 12 meses) + TOTAL
                $metrics['cases_created_by_month_last_12'] = $this->monthlyCountWithTotal('created_at', $rangeStart, $rangeEnd);

                //Casos firmados por mes (últimos 12 meses) + TOTAL
                $metrics['document_signed_by_month_last_12'] = $this->monthlyCountWithTotal('document_signing_date', $rangeStart, $rangeEnd);

                //Casos visitados por mes (últimos 12 meses) + TOTAL
                $metrics['inspections_by_month_last_12'] = $this->monthlyCountWithTotal('inspection_date', $rangeStart, $rangeEnd);

                //Presupuestos enviados por mes (últimos 12 meses) + TOTAL
                $metrics['budgets_sent_by_month_last_12'] = $this->monthlyCountWithTotal('budget_sending_date', $rangeStart, $rangeEnd);

                //Casos denunciados por mes (últimos 12 meses) + TOTAL
                $metrics['complaints_by_month_last_12'] = $this->monthlyCountWithTotal('complaint_date', $rangeStart, $rangeEnd);
                break;

            // case 2: // Otro rol (ejemplo)
            //     // aquí agregas/eliminas métricas específicas para ese rol
            //     break;

            default:
                // Para roles no mapeados, devolvemos solo las métricas base
                break;
        }

        return $this->success($metrics, 'Estadísticas para métricas de casos por rol', 200);
    }

    //Helper: primer día del mes de hace 12 meses (incluye ese día).
    private function firstDayMinus12Months(): string
    {
        return Carbon::now()->subMonthsNoOverflow(12)->startOfMonth()->toDateString();
    }

    //Helper: primer día del mes actual (excluye mes en curso en las series).
    private function firstDayCurrentMonth(): string
    {
        return Carbon::now()->startOfMonth()->toDateString();
    }

    //Construye serie por mes para los últimos 12 meses (excluye mes en curso) + fila TOTAL,
    private function monthlyCountWithTotal(string $column, string $start, string $end): array
    {
        $table      = (new CaseEntity)->getTable();
        $connection = (new CaseEntity)->getConnectionName();

        $sql = "
            SELECT YEAR($column) AS year, MONTH($column) AS month, COUNT(*) AS count
            FROM $table
            WHERE $column IS NOT NULL
              AND $column >= ?
              AND $column <  ?
            GROUP BY YEAR($column), MONTH($column)

            UNION ALL

            SELECT 'TOTAL' AS year, NULL AS month, COUNT(*) AS count
            FROM $table
            WHERE $column IS NOT NULL
              AND $column >= ?
              AND $column <  ?
            ORDER BY (year = 'TOTAL'), year, month
        ";

        $rows = DB::connection($connection)->select($sql, [$start, $end, $start, $end]);

        // Normalizamos tipos
        return array_map(function ($r) {
            return [
                'year'  => is_numeric($r->year) ? (int)$r->year : (string)$r->year, // 'TOTAL' o año numérico
                'month' => $r->month === null ? null : (int)$r->month,
                'count' => (int)$r->count,
            ];
        }, $rows);
    }
}
