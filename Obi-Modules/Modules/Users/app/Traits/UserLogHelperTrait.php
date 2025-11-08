<?php

namespace Modules\Users\app\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait UserLogHelperTrait
{
    // HELPERS PARA LOGS

    /** -----------------------------------------------------------
     *  BÁSICOS: nombres, columnas, formato y valores
     *  ----------------------------------------------------------- */
    protected function resolveUserName($userId): string
    {
        $id = (int)($userId ?? 0);
        if ($id <= 0) return '----';

        $u = DB::connection('traro_db')
            ->table('users')
            ->select(['name','username','email'])
            ->where('id', $id)->first();

        if (!$u) return '----';

        foreach ([(string)($u->name ?? ''), (string)($u->username ?? ''), (string)($u->email ?? '')] as $v) {
            $v = trim($v);
            if ($v !== '') return $v;
        }
        return '----';
    }

    protected function columnMap(): array
    {
        $path = base_path('Modules/Core/Config/column_map.php');
        if (file_exists($path)) {
            return include $path;
        }

        return (array) config('core.column_map', []);
    }

    protected function formatDateTime($value): array
    {
        try { $dt = $value ? Carbon::parse($value) : null; } catch (\Throwable $e) { $dt = null; }
        return [
            $dt ? $dt->format('d/m/Y') : '----/--/----',
            $dt ? $dt->format('H:i:s') : '--:--:--',
        ];
    }

    protected function humanValue($v): string
    {
        if ($v === null) return '----';
        if (is_bool($v)) return $v ? 'Sí' : 'No';
        if (is_numeric($v)) return (string)$v;
        if (is_string($v)) {
            $t = trim($v);
            if ($t === '') return '----';
            try {
                $dt = Carbon::parse($t);
                if ($dt && $dt->year >= 1900 && $dt->year <= 2100) {
                    return strpos($t, ':') !== false
                        ? $dt->format('d/m/Y H:i')
                        : $dt->format('d/m/Y');
                }
            } catch (\Throwable $e) {}
            return $t;
        }
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }

    /** -----------------------------------------------------------
     *  MODELOS Y EVENTOS
     *  ----------------------------------------------------------- */
    protected function normalizeModelKey(?string $name): ?string
    {
        if (!$name) return null;
        $name = strtolower(trim($name));

        $map = [
            'case'           => 'cases',
            'customer'       => 'customers',
            'user'           => 'users',
            'bank'           => 'banks',
            'insurer'        => 'insurers',
            'loss_adjuster'  => 'loss_adjusters',
            'configuration'  => 'configurations',
            'accident_type'  => 'accident_types',
            'auth'           => 'auth',
        ];

        return $map[$name] ?? $name;
    }

    protected function resolveModelName(int $modelId): ?string
    {
        if ($modelId <= 0) return null;
        try {
            return DB::connection('users_db')->table('model_logs')->where('id', $modelId)->value('name');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveEventName(int $eventId): ?string
    {
        if ($eventId <= 0) return null;
        try {
            return DB::connection('users_db')->table('events')->where('id', $eventId)->value('name');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** -----------------------------------------------------------
     *  COLUMNAS Y DIFERENCIAS
     *  ----------------------------------------------------------- */
    protected function labelFor(string $key, array $map, ?string $modelName = null): string
    {
        $section = $this->normalizeModelKey($modelName);

        if ($section && isset($map[$section]['map'][$key])) {
            return $map[$section]['map'][$key];
        }

        if (isset($map['__common']['map'][$key])) {
            return $map['__common']['map'][$key];
        }

        foreach ($map as $sec) {
            if (is_array($sec) && isset($sec['map'][$key])) {
                return $sec['map'][$key];
            }
        }

        return Str::title(str_replace('_', ' ', $key));
    }

    protected function diffSkipKeys(): array
    {
        return [
            'description',
            'sent_to_acepta',
            'available_notifications',
            'active_notifications',
            'schedule_message_sent',
            'schedule_message_confirmed',
            'schedule_liquidator_inspector_info',
        ];
    }

    protected function decodeJson($value): array
    {
        if (!$value) return [];
        if (is_array($value)) return $value;
        if (is_object($value)) return (array)$value;
        $d = json_decode((string)$value, true);
        return is_array($d) ? $d : [];
    }

    protected function normalizeForCompare($v)
    {
        $b = $this->boolish($v);
        if ($b !== null) return $b;

        if (is_string($v)) {
            $t = trim($v);
            if ($t === '') return null;
            if (is_numeric($t)) {
                return (strpos($t, '.') === false) ? (int)$t : (float)$t;
            }
            return $t;
        }

        if (is_bool($v) || is_int($v) || is_float($v) || $v === null) return $v;

        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function boolish($v): ?int
    {
        if (is_bool($v)) return $v ? 1 : 0;
        if (is_int($v) || is_float($v)) {
            if ($v === 0 || $v === 1) return (int)$v;
            return null;
        }
        if (is_string($v)) {
            $t = strtolower(trim($v));
            if ($t === 'true' || $t === '1')  return 1;
            if ($t === 'false' || $t === '0') return 0;
        }
        return null;
    }

    protected function changed($a, $b): bool
    {
        if ($a === $b) return false;
        if ((is_scalar($a) || $a === null) && (is_scalar($b) || $b === null)) return $a !== $b;
        return json_encode($a, JSON_UNESCAPED_UNICODE) !== json_encode($b, JSON_UNESCAPED_UNICODE);
    }

    protected function tsValue($value): int
    {
        try { return $value ? Carbon::parse($value)->getTimestamp() : 0; }
        catch (\Throwable $e) { return 0; }
    }

    protected function resolveForeignLabel(string $key, $value): ?string
    {
        if ($value === null || $value === '' || !is_scalar($value)) return null;
        $id = (int) $value;
        if ($id <= 0) return null;

        static $cache = [];

        // 🔗 Mapa: campo_id -> [conexion, tabla, pk, expresión etiqueta]
        $map = [
            // Usuarios de Traro
            'agent_id'        => ['traro_db','users','id',"COALESCE(name, username, email)"],
            'assigned_user'   => ['traro_db','users','id',"COALESCE(name, username, email)"],
            'created_by'      => ['traro_db','users','id',"COALESCE(name, username, email)"],
            'consultant_id'   => ['traro_db','users','id',"COALESCE(name, username, email)"], // ✅ FALTABA

            // Catálogos de Cases
            'priority_id'            => ['cases_db','priorities','id','name'],
            'accident_type_id'       => ['cases_db','accident_types','id','name'],
            'agreement_id'           => ['cases_db','agreements','id','name'],

            // Geography
            'commune_id'             => ['geography_db','communes','id','name'],

            // Bancos / aseguradoras / liquidadoras
            'bank_id'                => ['banks_db','banks','id','name'],
            'insurer_id'             => ['banks_db','insurers','id','name'],
            'loss_adjuster_id'       => ['banks_db','loss_adjusters','id','name'],
        ];

        if (!isset($map[$key])) return null;

        [$conn,$table,$idCol,$labelExpr] = $map[$key];
        $ck = "{$conn}.{$table}.{$id}";

        if (isset($cache[$ck])) return $cache[$ck];

        try {
            // value() no acepta expresiones -> usamos alias "label"
            $label = DB::connection($conn)
                ->table($table)
                ->where($idCol, $id)
                ->selectRaw($labelExpr.' as label')
                ->value('label');

            $label = is_string($label) ? trim($label) : null;
            if ($label === '') $label = null;

            return $cache[$ck] = $label;
        } catch (\Throwable $e) {
            return $cache[$ck] = null;
        }
    }

    protected function prettyValueForDiff(string $key, $v): string
    {
        if (str_starts_with($key, 'is_')) {
            $b = $this->boolish($v);
            if ($b !== null) return $b ? 'Sí' : 'No';
        }

        if ($key === 'state' && is_string($v)) {
            $last = preg_replace('~^.*\\\\~', '', $v);
            return $this->humanValue($last);
        }

        if (str_ends_with($key, '_id') || in_array($key, ['created_by','assigned_user','customer_id'], true)) {
            if (($label = $this->resolveForeignLabel($key, $v)) !== null) {
                return $label; // mostramos solo el nombre, no el ID
            }
        }

        return $this->humanValue($v);
    }

    /** -----------------------------------------------------------
     *  CONSTRUCCIÓN DE DESCRIPCIÓN DE LOGS
     *  ----------------------------------------------------------- */
    protected function buildDescriptionFromUserLogRow($row, array $columnMap): array
    {
        // details es la única fuente real; dentro vienen before/after
        $detRaw = $this->decodeJson($row->details ?? null);
        $before = isset($detRaw['before']) && is_array($detRaw['before']) ? $detRaw['before'] : [];
        $after  = isset($detRaw['after'])  && is_array($detRaw['after'])  ? $detRaw['after']  : [];

        $lines = [];
        $skip  = $this->diffSkipKeys();
        $modelName = $this->resolveModelName((int)($row->model_id ?? 0)) ?? null;
        $modelKey  = $this->normalizeModelKey($modelName);

        $coreCase = [
            'state','code','priority_id','created_at','agreement_id','property_address',
            'inspection_date','document_signing_date','complaint_date','collection_date',
            'budget_sending_date','settlement_report_date','probable_payment_date','online_collection_date',
            'accident_number','bank_service_number','advisory_amount','accident_type_id','is_duplicated',
            'date_of_loss','property_type','customer_id','commune_id','bank_id','insurer_id','loss_adjuster_id',
            'signature_status','denounce_status','scheduling_status','visit_status','budget_status',
            'decision_status','payment_status','overall_status','amount_owed','amount_paid','amount_owed_including_vat',
            'schedule_message_sent','schedule_message_confirmed','schedule_inspection_time'
        ];

        if (!empty($before) || !empty($after)) {
            $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

            foreach ($keys as $k) {
                if (in_array($k, $skip, true)) continue;

                $hasBefore = array_key_exists($k, $before);
                $hasAfter  = array_key_exists($k, $after);

                if ($hasBefore && $hasAfter) {
                    $v1 = $before[$k];
                    $v2 = $after[$k];
                    $n1 = $this->normalizeForCompare($v1);
                    $n2 = $this->normalizeForCompare($v2);

                    if ($n1 === $n2) continue;

                    $label = $this->labelFor($k, $columnMap, $modelName);
                    $label = str_replace(' (ID)', '', $label); // 🟢 limpia "(ID)"

                    $lines[] = sprintf(
                        'Cambió %s de %s a %s',
                        $label,
                        $this->prettyValueForDiff($k, $v1),
                        $this->prettyValueForDiff($k, $v2)
                    );
                    continue;
                }

                // 🔹 Permitir "de ---- a valor" solo si aparece solo en AFTER y es campo core
                if (!$hasBefore && $hasAfter) {
                    $isCase = ($modelKey === 'cases');
                    if (!$isCase || in_array($k, $coreCase, true)) {
                        $v2 = $after[$k];
                        $label = $this->labelFor($k, $columnMap, $modelName);
                        $label = str_replace(' (ID)', '', $label); // 🟢 limpia "(ID)"
                        $lines[] = 'Cambió '.$label.' de ---- a '.$this->prettyValueForDiff($k,$v2);
                    }
                    continue;
                }
            }

            if (!empty($lines)) {
                return $lines;
            }
        }

        // Fallback informativo (si no hubo diffs)
        $eventName = $this->resolveEventName((int)($row->event_id ?? 0)) ?: '----';
        $entityId = null;

        if (isset($row->entity_pk) && $row->entity_pk) {
            $entityId = (int)$row->entity_pk;
        } else {
            $entityId = (int) ($detRaw['after']['id'] ?? $detRaw['before']['id'] ?? ($detRaw['id'] ?? 0));
            $entityId = $entityId ?: null;
        }

        $lines[] = 'Evento: '.$eventName.'  Entidad: '.$modelName.'  ID: '.$this->humanValue($entityId);

        if (isset($detRaw['attempted'])) $lines[] = 'Intento: '.$this->humanValue($detRaw['attempted']);
        if (isset($detRaw['result']))    $lines[] = 'Resultado: '.$this->humanValue($detRaw['result']);

        return $lines;
    }
}
