<?php

namespace Modules\Cases\app\Http\Controllers;

use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\Stats;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Cases\Models\CaseEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
        $data  = $request->validate(['name' => 'required|string']);
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

    // Endpoints de métricas

    // Metodo que devuelve la suma de amount_paid filtrando por probable_payment_date
    public function statsAmountPaid(?int $year = null, ?int $month = null): JsonResponse
    {
        $this->assertYearMonth($year, $month);
        [$start, $end] = $this->buildDateRange($year, $month);

        $casesTable = (new CaseEntity)->getTable();

        $q = CaseEntity::query()
            ->whereRaw($this->notTestCustomersSql($casesTable))
            ->where('amount_paid', '>', 0);

        if ($start && $end) {
            $q->where(function ($w) use ($start, $end) {
                $w->whereBetween('probable_payment_date', [$start, $end])
                  ->orWhere(function ($w2) use ($start, $end) {
                      $w2->whereNull('probable_payment_date')
                         ->whereBetween('created_at', [$start, $end]);
                  });
            });
        }
        $value = (int) ($q->sum('amount_paid') ?? 0);

        return $this->success([
            'year'  => $year,
            'month' => $month,
            'value' => $value,
        ], 'Listado de pagos', 200);
    }

    // Metodo que devuelve el conteo de casos creados (created_at) con filtro opcional por ejecutivo
    public function statsCasesCreated(?int $year = null, ?int $month = null, ?int $agent = null): JsonResponse
    {
        $this->assertYearMonth($year, $month);
        [$start, $end] = $this->buildDateRange($year, $month);

        $casesTable = (new CaseEntity)->getTable();

        $q = CaseEntity::query()
            ->whereRaw($this->notTestCustomersSql($casesTable));

        if ($start && $end) {
            $q->whereBetween('created_at', [$start, $end]);
        }

        // Filtro por ejecutivo: customers.assigned_agent debe ser igual a {agent}
        if (!is_null($agent)) {
            $customersDb = Config::get('database.connections.customers_db.database');
            $q->whereExists(function ($sub) use ($customersDb, $casesTable, $agent) {
                $sub->select(DB::raw(1))
                    ->from("{$customersDb}.customers as cust")
                    ->whereRaw("cust.id = {$casesTable}.customer_id")
                    ->where('cust.assigned_agent', '=', (int) $agent);
            });
        }

        $value = (int) $q->count();

        return $this->success([
            'year'            => $year,
            'month'           => $month,
            'agent'           => $agent,
            'value'           => $value,
        ], 'Casos ingresados', 200);
    }

    // Metodo que devuelve el conteo de casos firmados (document_signing_date)
    public function statsCasesSigned(?int $year = null, ?int $month = null): JsonResponse
    {
        $this->assertYearMonth($year, $month);
        [$start, $end] = $this->buildDateRange($year, $month);

        $column = 'document_signing_date';

        $q = CaseEntity::query()
            ->whereNotNull($column)
            ->whereRaw($this->notTestCustomersSql((new CaseEntity)->getTable()));

        if ($start && $end) {
            $q->whereBetween($column, [$start, $end]);
        }

        $value = (int) $q->count();

        return $this->success([
            'year'            => $year,
            'month'           => $month,
            'value'           => $value,
        ], 'Casos firmados', 200);
    }

    // Metodo que devuelve el conteo de inspecciones (inspection_date) realizadas con filtro opcional por asesor
    public function statsCasesInspected(?int $year = null, ?int $month = null, ?int $advisor = null): JsonResponse
    {
        $this->assertYearMonth($year, $month);
        [$start, $end] = $this->buildDateRange($year, $month);

        $column = 'inspection_date';

        // Se usan los strings exactos para evitar discrepancias con el FQCN de PHP
        $allowedStates = [
            'Modules\\Cases\\States\\Traro\\Presupuesto',
            'Modules\\Cases\\States\\Traro\\Liquidacion',
            'Modules\\Cases\\States\\Traro\\Recaudacion',
        ];

        $q = CaseEntity::query()
            ->whereNotNull($column)
            ->where('softdeleted', 0)
            ->whereIn('state', $allowedStates)
            ->whereRaw($this->notTestCustomersSql((new CaseEntity)->getTable()));

        if ($start && $end) {
            $q->whereBetween($column, [$start, $end]);
        }

        if (!is_null($advisor)) {
            $q->where('consultant_id', (int) $advisor);
        }

        $value = (int) $q->distinct()->count((new CaseEntity)->getTable() . '.id');

        return $this->success([
            'year'    => $year,
            'month'   => $month,
            'advisor' => $advisor,
            'value'   => $value,
        ], 'Casos visitados', 200);
    }

    // Metodo que devuelve el conteo de presupuestos enviados (budget_sending_date)
    public function statsBudgetsSent(?int $year = null, ?int $month = null): JsonResponse
    {
        $this->assertYearMonth($year, $month);
        [$start, $end] = $this->buildDateRange($year, $month);

        $column = 'budget_sending_date';

        $q = CaseEntity::query()
            ->whereNotNull($column)
            ->where('softdeleted', 0)
            ->whereRaw($this->notTestCustomersSql((new CaseEntity)->getTable()));

        if ($start && $end) {
            $q->whereBetween($column, [$start, $end]);
        }

        $value = (int) $q->distinct()->count((new CaseEntity)->getTable() . '.id');

        return $this->success([
            'year'            => $year,
            'month'           => $month,
            'value'           => $value,
        ], 'Presupuestos enviados', 200);
    }

    // Metodo que devuelve el conteo de denuncios (complaint_date)
    public function statsComplaints(?int $year = null, ?int $month = null): JsonResponse
    {
        $this->assertYearMonth($year, $month);
        [$start, $end] = $this->buildDateRange($year, $month);

        $column = 'complaint_date';

        $q = CaseEntity::query()
            ->whereNotNull($column)
            ->whereRaw($this->notTestCustomersSql((new CaseEntity)->getTable()));

        if ($start && $end) {
            $q->whereBetween($column, [$start, $end]);
        }

        $value = (int) $q->count();

        return $this->success([
            'year'            => $year,
            'month'           => $month,
            'value'           => $value,
        ], 'Casos denunciados', 200);
    }

    // Helpers internos

    // Metodo que valida coherencia de year y month (month=0 se considera sin mes)
    private function assertYearMonth(?int &$year, ?int &$month): void
    {
        // Normalizamos month=0 como null para permitir rutas tipo /{year}/0
        if ($month === 0) {
            $month = null;
        }

        if (!is_null($month) && is_null($year)) {
            abort(422, 'If month is provided, year is required.');
        }
        if (!is_null($year) && ($year < 2000 || $year > 2100)) {
            abort(422, 'Invalid year.');
        }
        if (!is_null($month) && ($month < 1 || $month > 12)) {
            abort(422, 'Invalid month.');
        }
    }

    // Metodo que construye el rango [inicio, fin-exclusivo] segun year/month (month=0 => sin mes)
    private function buildDateRange(?int $year, ?int $month): array
    {
        // Historico sin filtros
        if (is_null($year) && is_null($month)) {
            return [null, null];
        }

        // Solo año
        if (!is_null($year) && is_null($month)) {
            $start = Carbon::create($year, 1, 1, 0, 0, 0);
            $end   = (clone $start)->addYear();
            return [$start, $end];
        }

        // Año y mes especifico
        $start = Carbon::create($year, $month, 1, 0, 0, 0);
        $end   = (clone $start)->addMonth();
        return [$start, $end];
    }

    // Metodo que devuelve una clausula NOT EXISTS para excluir clientes con "test" en su nombre
    private function notTestCustomersSql(string $casesTable): string
    {
        $customersDb = Config::get('database.connections.customers_db.database');

        return "
            NOT EXISTS (
                SELECT 1
                FROM {$customersDb}.customers cust
                WHERE cust.id = {$casesTable}.customer_id
                  AND LOWER(CONCAT_WS(' ',
                        COALESCE(cust.full_name, ''),
                        COALESCE(cust.name, ''),
                        COALESCE(cust.lastname, '')
                  )) LIKE '%test%'
            )
        ";
    }
}
