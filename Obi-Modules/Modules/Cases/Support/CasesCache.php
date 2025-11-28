<?php

namespace Modules\Cases\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Cases\Models\CaseDetail;
use Illuminate\Database\Eloquent\Builder;
use Modules\Cases\Models\CaseEntity;
use Illuminate\Support\Facades\DB;

class CasesCache
{
    public const CACHE_KEY_ALL       = 'cases.all';
    public const CACHE_KEY_LAST_SYNC = 'cases.last_sync';

    /**
     * Devuelve TODOS los casos desde cache,
     * asegurando antes que el cache está construido y sincronizado por delta.
     */
    public static function getAll(): array
    {
        if (! Cache::has(self::CACHE_KEY_ALL)) {
            self::buildAll();
        }

        self::syncDelta();

        return Cache::get(self::CACHE_KEY_ALL, []);
    }

    /**
     * Construye el cache completo (cache frío).
     * Lee TODA la vista v_cases_details y guarda:
     *  - cases.all = [case_id => payload]
     *  - cases.last_sync = max(updated_at)
     */
    public static function buildAll(): void
    {
        $payload      = [];
        $maxUpdatedAt = null;

        DB::connection('cases_db')
            ->table('v_cases_details')
            ->orderBy('id')
            ->chunk(1000, function ($chunk) use (&$payload, &$maxUpdatedAt) {
                foreach ($chunk as $row) {
                    $rowArr = (array) $row;
                    $id     = $rowArr['id'] ?? null;

                    if ($id === null) {
                        continue;
                    }

                    // Guardamos todo el payload tal cual
                    $payload[$id] = $rowArr;

                    // updated_at viene de la tabla cases (c.*)
                    $updated = $rowArr['updated_at'] ?? null;
                    if (! $updated) {
                        continue;
                    }

                    // Normalizar updated_at solo si está en formato ISO (YYYY-MM-DD ...)
                    if ($updated instanceof Carbon) {
                        $dt = $updated;
                    } elseif (is_string($updated) && preg_match('/^\d{4}-\d{2}-\d{2}/', $updated)) {
                        try {
                            $dt = Carbon::parse($updated);
                        } catch (\Throwable $e) {
                            // Si no se puede parsear, lo ignoramos para maxUpdatedAt
                            continue;
                        }
                    } else {
                        // Formato tipo 13/11/2025 17:31:38 u otros → ignorar para maxUpdatedAt
                        continue;
                    }

                    if ($maxUpdatedAt === null || $dt->gt($maxUpdatedAt)) {
                        $maxUpdatedAt = $dt;
                    }
                }
            });

        Cache::forever(self::CACHE_KEY_ALL, $payload);

        if ($maxUpdatedAt) {
            Cache::forever(self::CACHE_KEY_LAST_SYNC, $maxUpdatedAt->toDateTimeString());
        } else {
            Cache::forever(self::CACHE_KEY_LAST_SYNC, null);
        }
    }

    /**
     * Sincroniza solo los casos cuyo updated_at > last_sync.
     * Usa tabla cases para detectar IDs, y v_cases_details para recomponer el payload.
     */
    public static function syncDelta(): void
    {
        $lastSync = Cache::get(self::CACHE_KEY_LAST_SYNC);

        // Si no hay last_sync, reconstruimos completo
        if (! $lastSync) {
            self::buildAll();
            return;
        }

        // Normalizar lastSync por si acaso
        try {
            $lastSyncDt = $lastSync instanceof Carbon
                ? $lastSync
                : Carbon::parse((string) $lastSync);
        } catch (\Throwable $e) {
            // Si está corrupto, reconstruimos todo
            self::buildAll();
            return;
        }

        // 1) Casos que cambiaron desde last_sync (tabla cases)
        $changed = CaseEntity::withoutGlobalScope('exclude_softdeleted')
            ->select('id', 'updated_at')
            ->where('updated_at', '>', $lastSyncDt->toDateTimeString())
            ->orderBy('updated_at')
            ->get();

        if ($changed->isEmpty()) {
            return;
        }

        $ids          = $changed->pluck('id')->all();
        $cacheAll     = Cache::get(self::CACHE_KEY_ALL, []);
        $maxUpdatedAt = $lastSyncDt->copy();

        // 2) Cargamos solo esos IDs desde la vista
        $details = DB::connection('cases_db')
            ->table('v_cases_details')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($changed as $case) {
            $id = $case->id;

            // updated_at de la TABLA cases → siempre en formato ISO
            try {
                $caseUpdatedAt = $case->updated_at instanceof Carbon
                    ? $case->updated_at
                    : Carbon::parse((string) $case->updated_at);
            } catch (\Throwable $e) {
                // Si algo raro pasa, seguimos con el siguiente
                continue;
            }

            if ($caseUpdatedAt->gt($maxUpdatedAt)) {
                $maxUpdatedAt = $caseUpdatedAt;
            }

            if ($details->has($id)) {
                // La vista devuelve fila → actualizar payload
                $cacheAll[$id] = (array) $details->get($id);
            } else {
                // La vista no tiene este id → probablemente softdeleted=1 → lo sacamos del cache
                unset($cacheAll[$id]);
            }
        }

        Cache::forever(self::CACHE_KEY_ALL, $cacheAll);
        Cache::forever(self::CACHE_KEY_LAST_SYNC, $maxUpdatedAt->toDateTimeString());
    }

    /**
     * Sincroniza un solo caso por ID.
     * Útil para Observer o para hooks explícitos en otros módulos.
     */
    public static function syncOne(int $caseId): void
    {
        $cacheAll = Cache::get(self::CACHE_KEY_ALL, []);

        // Traer la fila desde la vista usando DB directo (como en buildAll/syncDelta)
        $detail = DB::connection('cases_db')
            ->table('v_cases_details')
            ->where('id', $caseId)
            ->first();

        if ($detail) {
            $row = (array) $detail;
            $cacheAll[$caseId] = $row;

            // Actualizar last_sync solo si updated_at viene en formato ISO
            $updated = $row['updated_at'] ?? null;
            if ($updated) {
                $updatedAt = null;

                if ($updated instanceof Carbon) {
                    $updatedAt = $updated;
                } elseif (is_string($updated) && preg_match('/^\d{4}-\d{2}-\d{2}/', $updated)) {
                    try {
                        $updatedAt = Carbon::parse($updated);
                    } catch (\Throwable $e) {
                        // si no se puede parsear, lo ignoramos
                        $updatedAt = null;
                    }
                }

                if ($updatedAt) {
                    $currentLastSync = Cache::get(self::CACHE_KEY_LAST_SYNC);

                    if (! $currentLastSync) {
                        Cache::forever(self::CACHE_KEY_LAST_SYNC, $updatedAt->toDateTimeString());
                    } else {
                        try {
                            $current = $currentLastSync instanceof Carbon
                                ? $currentLastSync
                                : Carbon::parse((string) $currentLastSync);

                            if ($updatedAt->gt($current)) {
                                Cache::forever(self::CACHE_KEY_LAST_SYNC, $updatedAt->toDateTimeString());
                            }
                        } catch (\Throwable $e) {
                            // si last_sync está raro, lo sobreescribimos con la nueva fecha válida
                            Cache::forever(self::CACHE_KEY_LAST_SYNC, $updatedAt->toDateTimeString());
                        }
                    }
                }
            }
        } else {
            // Si la vista no tiene este id → probablemente softdeleted=1 → lo sacamos del cache
            unset($cacheAll[$caseId]);
        }

        Cache::forever(self::CACHE_KEY_ALL, $cacheAll);
    }

    public static function getAllCasesRaw(): array
    {
        // Solo lectura del snapshot oficial
        return Cache::get(self::CACHE_KEY_ALL, []);
    }
    public static function storeAllCases(array $data): void
    {
        //Solo para realizar pruebas manuales de carga
    }

    //Metodo centralizado para que cuando una entidad mute tambien se refresque el cache de su registro
    public static function refreshBy(string $type, int $relatedId): void
    {
        // Mapa centralizado: tipo lógico → columna en cases
        $map = [
            'customer'      => 'customer_id',
            'bank'          => 'bank_id',
            'insurer'       => 'insurer_id',
            'loss_adjuster' => 'loss_adjuster_id',
            'accident_type' => 'accident_type_id',
            'priority'      => 'priority_id',
            'agreement'     => 'agreement_id',
            // agrega más si los necesitas
        ];

        $column = $map[$type] ?? null;
        if (! $column) {
            // tipo no reconocido → no hacemos nada
            return;
        }

        // 1) IDs de casos que referencian esta entidad
        $caseIds = \Modules\Cases\Models\CaseEntity::withoutGlobalScope('exclude_softdeleted')
            ->where($column, $relatedId)
            ->pluck('id')
            ->all();

        if (empty($caseIds)) {
            return;
        }

        // 2) Snapshot actual del cache
        $cacheAll = Cache::get(self::CACHE_KEY_ALL, []);

        // 3) Traer TODAS las filas de la vista en una sola consulta
        $details = DB::connection('cases_db')
            ->table('v_cases_details')
            ->whereIn('id', $caseIds)
            ->get()
            ->keyBy('id');

        // 4) Actualizar/limpiar el cache para esos IDs
        foreach ($caseIds as $caseId) {
            $caseId = (int) $caseId;

            if ($details->has($caseId)) {
                // La vista devuelve el caso (no softdeleted) → actualizar payload
                $cacheAll[$caseId] = (array) $details->get($caseId);
            } else {
                // No aparece en la vista (probablemente softdeleted=1) → sacarlo del cache
                unset($cacheAll[$caseId]);
            }
        }

        // 5) Guardar snapshot actualizado
        Cache::forever(self::CACHE_KEY_ALL, $cacheAll);

        // 👀 Nota:
        // No tocamos cases.last_sync aquí, porque no estamos cambiando updated_at en la tabla cases.
    }
}
