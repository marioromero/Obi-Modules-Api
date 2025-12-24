<?php

namespace Modules\Configurations\app\Helpers;

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
}
