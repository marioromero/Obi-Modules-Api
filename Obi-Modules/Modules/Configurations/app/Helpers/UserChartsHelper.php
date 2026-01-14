<?php

namespace Modules\Configurations\app\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
class UserChartsHelper
{
    public static function normalizeScope(string $scope): string|false
    {
        $s = mb_strtolower(trim($scope), 'UTF-8');
        return in_array($s, ['by-user', 'by-rol'], true) ? $s : false;
    }

    /**
     * Obtiene el siguiente chart_id GLOBAL (user + rol)
     */
    public static function nextGlobalChartId(array $userCharts, array $rolCharts): int
    {
        $max = 0;

        foreach (array_merge($userCharts, $rolCharts) as $c) {
            $id = (int) ($c['chart_id'] ?? 0);
            if ($id > $max) $max = $id;
        }

        return $max + 1;
    }

    public static function findChartIndexById(array $charts, int $chartId): ?int
    {
        foreach ($charts as $i => $c) {
            if ((int) ($c['chart_id'] ?? 0) === $chartId) {
                return $i;
            }
        }
        return null;
    }

public static function formatAxisValue(mixed $value, string $tz = 'America/Santiago'): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $v = trim($value);

        // 1) Si parece namespace/clase (contiene backslashes) => último segmento
        if (str_contains($v, '\\')) {
            $v = Str::afterLast($v, '\\');
        }

        // 2) YYYY-MM => MM/YY
        if (preg_match('/^\d{4}-\d{2}$/', $v)) {
        try {
            $dt = Carbon::createFromFormat('Y-m-d', $v . '-01', $tz);
            return $dt->format('m/y'); // MM/YY
        } catch (\Throwable $e) {
            return $v;
        }
    }

        // 3) YYYY-MM-DD => DD/MM/YYYY
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            try {
                $dt = Carbon::createFromFormat('Y-m-d', $v, $tz);
                return $dt->format('d/m/Y');
            } catch (\Throwable $e) {
                return $v;
            }
        }

        // 4) Datetime tipo "YYYY-MM-DD HH:MM:SS" (u otros parseables)
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $v)) {
            try {
                $dt = Carbon::createFromFormat('Y-m-d H:i:s', $v, $tz);
                return $dt->format('d/m/Y');
            } catch (\Throwable $e) {
                return $v;
            }
        }

        return $v;
    }

    /**
     * Normaliza el valor de la serie (eje_y):
     * - si viene numérico como string => lo castea (int/float) para que el front no reciba "123"
     */
    public static function normalizeSeriesValue(mixed $value): mixed
    {
        if (is_int($value) || is_float($value)) return $value;

        if (is_string($value)) {
            $v = trim($value);

            // entero
            if (preg_match('/^-?\d+$/', $v)) return (int) $v;

            // decimal (con punto)
            if (preg_match('/^-?\d+\.\d+$/', $v)) return (float) $v;
        }

        return $value;
    }

    public static function sanitizeSql(string $sql): string
    {
        // Si ya está usando CHAR(92), no tocar (idempotente)
        if (stripos($sql, 'CHAR(92)') !== false) {
            return $sql;
        }

        /**
         * Reemplaza cualquier variante de:
         *   SUBSTRING_INDEX(campo, '\', -1)
         *   SUBSTRING_INDEX(campo, '\\', -1)
         *   SUBSTRING_INDEX(campo, '\\\\', -1)
         * por:
         *   SUBSTRING_INDEX(campo, CHAR(92), -1)
         *
         * OJO: en el patrón, '\\\\*' significa "uno o más backslashes dentro del string SQL".
         */
        $pattern = "/SUBSTRING_INDEX\\s*\\(\\s*([^,]+)\\s*,\\s*'\\\\\\\\*'\\s*,\\s*(-?\\d+)\\s*\\)/i";

        $fixed = preg_replace($pattern, "SUBSTRING_INDEX($1, CHAR(92), $2)", $sql);

        return $fixed ?? $sql;
    }
}
