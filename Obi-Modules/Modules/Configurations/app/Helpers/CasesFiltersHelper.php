<?php

namespace Modules\Configurations\app\Helpers;

use Illuminate\Support\Facades\DB;

class CasesFiltersHelper
{
    /**
     * Devuelve la condición SQL por estado (paso).
     */
    public static function stateCond(string $step): ?string
    {
        $label = [
            'denuncio'     => 'Denuncio',
            'programacion' => 'Programacion',
            'visita'       => 'Visita',
            'presupuesto'  => 'Presupuesto',
            'liquidacion'  => 'Liquidacion',
            'recaudacion'  => 'Recaudacion',
        ][$step] ?? null;

        if (! $label) {
            return null;
        }

        return "SUBSTRING_INDEX(REPLACE(state, '\\\\', '/'), '/', -1) = '{$label}'";
    }

    /**
     * Enriquecer datos con phone y user_name desde customers_db.
     */
 public static function enrichWithCustomerData(array $rows): array
{
    if (empty($rows)) return $rows;

    // --- 1) Recolectar IDs de clientes ---
    $customerIds = [];
    foreach ($rows as $r) {
        if (isset($r->customer_id)) {
            $customerIds[] = (int) $r->customer_id;
        }
    }

    $customerIds = array_values(array_unique(array_filter($customerIds)));
    if (! $customerIds) return $rows;

    // --- 2) Cargar datos extra desde v_customers_details ---
    $extras = DB::connection('customers_db')
        ->table('v_customers_details')
        ->whereIn('id', $customerIds)
        ->get(['id', 'phone', 'user_name'])
        ->keyBy('id');

    // --- 3) Enriquecer filas ---
    foreach ($rows as $r) {
        $cid = isset($r->customer_id) ? (int)$r->customer_id : null;

        // Datos extra si existen
        if ($cid && $extras->has($cid)) {
            $extra = $extras->get($cid);

            if (! isset($r->phone) || $r->phone === null) {
                $r->phone = $extra->phone ?? null;
            }

            if (! isset($r->user_name) || $r->user_name === null) {
                $r->user_name = $extra->user_name ?? null;
            }
        }

        // --- 4) NORMALIZAR JSON FEOS (case_flow_last_json) ---
        if (!empty($r->case_flow_last_json) && is_string($r->case_flow_last_json)) {

            $decoded = json_decode($r->case_flow_last_json, true);

            // Si es JSON válido → reemplazar por el array (sin escapes)
            if (json_last_error() === JSON_ERROR_NONE) {
                $r->case_flow_last_json = $decoded;
            }
        }
    }

    // --- 5) Retornar filas enriquecidas y con JSON limpio ---
    return $rows;
}

    /**
     * Proyectar SÓLO las columnas necesarias.
     */
    public static function projectRows(array $rows, array $columnsEn): array
    {
        $selectCols = array_values(array_unique(array_merge(['id'], $columnsEn)));

        return array_map(function ($r) use ($selectCols) {
            $out = [];
            foreach ($selectCols as $c) {
                $out[$c] = property_exists($r, $c) ? $r->{$c} : null;
            }
            return $out;
        }, $rows);
    }

    /**
     * Eliminar duplicados por ID.
     */
    public static function dedupRows(array $rows): array
    {
        return collect($rows)->unique('id')->values()->all();
    }

    /**
     * Eliminar registros "Test".
     */
    public static function filterOutTest(array $rows): array
    {
        return array_values(
            array_filter($rows, fn($r) => stripos($r->customer_name ?? '', 'test') === false)
        );
    }

    /**
     * Calcular managed_cases (default o filters).
     */
    public static function calcManagedCases(
        string $step,
        array $stepCfg,
        array $defaultColumnsEn,
        string $baseSql,
        string $baseOrder
    ): array {
        $managedMonths = (int)($stepCfg['managed_cases']['months'] ?? 2);
        $targetStep    = $stepCfg['managed_cases']['target_step'] ?? null;

        $managedData = [];

        // Caso especial: Recaudación = pagados recientes
        if ($step === 'recaudacion') {
            $whereReca = static::stateCond('recaudacion');

            if ($whereReca) {
                $window = "collection_date >= DATE_SUB(NOW(), INTERVAL {$managedMonths} MONTH)";
                $status = "LOWER(payment_status) = 'pagado' AND collection_date IS NOT NULL";

                $sql = $baseSql . "WHERE {$whereReca} AND {$status} AND {$window}
                                   ORDER BY collection_date DESC, id DESC";

                try {
                    $managedData = DB::connection('cases_db')->select($sql);
                } catch (\Throwable $e) {
                    $managedData = [];
                }

                $managedData = static::dedupRows($managedData);
                $managedData = static::filterOutTest($managedData);
                $managedData = static::enrichWithCustomerData($managedData);
                $managedData = static::projectRows($managedData, $defaultColumnsEn);
            }

            return ['months' => $managedMonths, 'data' => $managedData];
        }

        // Otros pasos con target_step
        if ($targetStep) {
            $tWhere = static::stateCond($targetStep);

            if ($tWhere) {
                $tWhere = "({$tWhere}) AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')";
                $window = "created_at >= DATE_SUB(NOW(), INTERVAL {$managedMonths} MONTH)";

                $isTargetReca = (mb_strtolower($targetStep, 'UTF-8') === 'recaudacion');
                $mOrder = $isTargetReca
                    ? "ORDER BY COALESCE(probable_payment_date, created_at) ASC, id DESC"
                    : $baseOrder;

                $mSql = $baseSql . "WHERE {$tWhere} AND {$window} {$mOrder}";

                try {
                    $managedData = DB::connection('cases_db')->select($mSql);
                } catch (\Throwable $e) {
                    $managedData = [];
                }

                $managedData = static::dedupRows($managedData);
                $managedData = static::filterOutTest($managedData);
                $managedData = static::enrichWithCustomerData($managedData);
                $managedData = static::projectRows($managedData, $defaultColumnsEn);
            }
        }

        return ['months' => $managedMonths, 'data' => $managedData];
    }
}
