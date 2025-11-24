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

    protected function resolveUserNamesByIds(array $ids): array
    {
        // Normaliza y filtra enteros únicos preservando orden
        $seen = [];
        $norm = [];
        foreach ($ids as $x) {
            $id = (int) $x;
            if ($id > 0 && !isset($seen[$id])) {
                $seen[$id] = true;
                $norm[] = $id;
            }
        }
        if (empty($norm)) return [];

        // Cache local por request para no repetir consultas
        static $cache = []; // [id => "Nombre/username/email"]

        // IDs que faltan en cache
        $missing = array_values(array_filter($norm, fn($id) => !isset($cache[$id])));

        if (!empty($missing)) {
            try {
                $rows = DB::connection('traro_db')
                    ->table('users')
                    ->select(['id', DB::raw('COALESCE(name, username, email) as label')])
                    ->whereIn('id', $missing)
                    ->get();

                foreach ($rows as $r) {
                    $label = is_string($r->label) ? trim($r->label) : '';
                    $cache[(int)$r->id] = $label !== '' ? $label : (string) $r->id;
                }

                // Si algún id no existía, deja el id como texto
                foreach ($missing as $m) {
                    if (!isset($cache[$m])) {
                        $cache[$m] = (string) $m;
                    }
                }
            } catch (\Throwable $e) {
                // Ante error de DB, devolvemos los ids como string
                return array_map(fn($id) => (string) $id, $norm);
            }
        }

        // Devuelve nombres en el MISMO orden que llegaron los ids
        return array_map(fn($id) => $cache[$id] ?? (string) $id, $norm);
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
        $tz = config('app.timezone', 'America/Santiago');

        try {
            $dt = $value ? Carbon::parse($value, $tz) : null;
        } catch (\Throwable $e) {
            $dt = null;
        }

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

            // Intentar tratarlo como fecha/hora
            try {
                $tz = config('app.timezone', 'America/Santiago');
                $dt = Carbon::parse($t, $tz);

                if ($dt && $dt->year >= 1900 && $dt->year <= 2100) {
                    return str_contains($t, ':')
                        ? $dt->format('d/m/Y H:i')
                        : $dt->format('d/m/Y');
                }
            } catch (\Throwable $e) {
                // no era fecha, seguimos abajo
            }

            // No era fecha → devolver texto tal cual
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

    protected function isDeleteEvent(int $eventId): bool
    {
        $name = $this->resolveEventName($eventId) ?? '';
        $name = strtolower($name);
        return str_starts_with($name, 'delete_') || $name === 'delete';
    }
    protected function diffSkipKeys(): array
    {
        return [
            'available_notifications',
            'active_notifications',
            'is_enabled',
            'updated_at',
            'softdeleted',
            'sent_to_acepta',
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

            // Números en string
            if (is_numeric($t)) {
                return (strpos($t, '.') === false) ? (int)$t : (float)$t;
            }

            // Fechas
            try {
                $tz = config('app.timezone', 'America/Santiago');
                $dt = Carbon::parse($t, $tz);
                if ($dt && $dt->year >= 1900 && $dt->year <= 2100) {
                    // comparar por instante, no por formato
                    return $dt->getTimestamp();
                }
            } catch (\Throwable $e) {
                // no es fecha → seguimos
            }

            // cualquier otro string
            return $t;
        }

        if (is_bool($v) || is_int($v) || is_float($v) || $v === null) return $v;

        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function humanModelLabel(?string $modelName): string
    {
        $key = $this->normalizeModelKey($modelName);

        return match ($key) {
            'cases'            => 'caso',
            'customers'        => 'cliente',
            'users'            => 'usuario',
            'banks'            => 'banco',
            'insurers'         => 'aseguradora',
            'loss_adjusters'   => 'liquidadora',
            'configurations'   => 'configuración',
            'accident_types'   => 'tipo de siniestro',
            'auth'             => 'sesión',
            default            => $modelName ?: 'registro',
        };
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
        // Para escalares (incluyendo strings de fecha), usamos normalizeForCompare
        if ((is_scalar($a) || $a === null) && (is_scalar($b) || $b === null)) {
            $na = $this->normalizeForCompare($a);
            $nb = $this->normalizeForCompare($b);

            return $na !== $nb;
        }

        // Para arrays/objetos, comparamos por JSON
        return json_encode($a, JSON_UNESCAPED_UNICODE) !== json_encode($b, JSON_UNESCAPED_UNICODE);
    }


    protected function tsValue($value): int
    {
        try {
            if (!$value) return 0;
            $tz = config('app.timezone', 'America/Santiago');
            return Carbon::parse($value, $tz)->getTimestamp();
        } catch (\Throwable $e) {
            return 0;
        }
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

    protected function prettyTagsString(string $t): string
    {
        $t = trim($t);
        $t = trim($t, "[] \t\n\r\0\x0B");

        if ($t === '') {
            return '----';
        }

        $active = [];

        if (preg_match_all('/name:([^,]+),enabled:(true|false)/', $t, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name    = trim($m[1]);
                $enabled = strtolower($m[2]) === 'true';

                if ($enabled && $name !== '') {
                    $active[] = $name;
                }
            }
        }

        if (empty($active)) {
            return '----';
        }

        return implode(', ', $active);
    }

    protected function prettyValueForDiff(string $key, $v): string
    {
        // 🔹 NUEVO: campos tipo flag que queremos ver como Sí / No
        if ($key === 'sent_to_acepta') {
            $b = $this->boolish($v); // interpreta 1, 0, "1", "0", "true", "false"
            if ($b !== null) {
                return $b ? 'Sí' : 'No';
            }
            // si no se puede interpretar como booleano, sigue el flujo normal
        }

        // 🔸 Booleanos tipo is_active, is_duplicated, etc.
        if (str_starts_with($key, 'is_')) {
            $b = $this->boolish($v);
            if ($b !== null) return $b ? 'Sí' : 'No';
        }

        // 🔸 Estados tipo Modules\Cases\States\Traro\Ingreso → "Ingreso"
        if ($key === 'state' && is_string($v)) {
            $last = preg_replace('~^.*\\\\~', '', $v);
            return $this->humanValue($last);
        }

        // 🔸 Campos *_id o foreign keys conocidas → nombre legible
        if (str_ends_with($key, '_id') || in_array($key, ['created_by','assigned_user','customer_id'], true)) {
            if (($label = $this->resolveForeignLabel($key, $v)) !== null) {
                return $label; // mostramos solo el nombre, no el ID
            }
        }

        // 🔹 Caso especial: strings de etiquetas tipo [name:...,enabled:true,color:...]
        if (is_string($v)) {
            $t = trim($v);
            if ($t !== '' && str_contains($t, 'name:') && str_contains($t, 'enabled:')) {
                return $this->prettyTagsString($t);
            }
        }

        // 🔹 Arrays: usuarios asignados, tags, etc.
        if (is_array($v)) {

            // 1) Detectar si es un array de etiquetas: [ [name, enabled, color], ... ]
            if ($key === 'tags') {
                $tagNames = [];

                foreach ($v as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $name = trim((string)($item['name'] ?? $item['label'] ?? ''));
                    $enabled = array_key_exists('enabled', $item)
                        ? (bool)$item['enabled']
                        : true; // si no trae enabled, asumimos true

                    if ($enabled && $name !== '') {
                        $tagNames[] = $name;
                    }
                }

                if (empty($tagNames)) {
                    return '----';
                }

                // Ej: "Con comentarios, Deudor"
                return implode(', ', $tagNames);
            }

            // 2) Caso común {"user_assigned":[21,22,11,...]}
            if (isset($v['user_assigned']) && is_array($v['user_assigned'])) {
                $names = $this->resolveUserNamesByIds($v['user_assigned']);
                return 'Usuarios asignados: '.implode(', ', $names);
            }

            // 3) Si es un array simple de IDs numéricos [21,22,11,...]
            $allInts = true;
            foreach ($v as $i) {
                if (!is_int($i) && !(is_string($i) && ctype_digit($i))) {
                    $allInts = false;
                    break;
                }
            }
            if ($allInts && !empty($v)) {
                $names = $this->resolveUserNamesByIds($v);
                return implode(', ', $names);
            }

            // 4) Cualquier otro array → salida genérica legible
            return trim(str_replace(['{','}','"'], '', json_encode($v, JSON_UNESCAPED_UNICODE)));
        }

        // 🔸 Fallback: valor normal
        return $this->humanValue($v);
    }

    // para mostrar legibles los comentarios
    protected function buildCommentDiffLines($beforeDesc, $afterDesc): array
    {
        $beforeComments = [];
        $afterComments  = [];

        // Extraer comments de BEFORE
        if (is_array($beforeDesc) && isset($beforeDesc['comments']) && is_array($beforeDesc['comments'])) {
            $beforeComments = $beforeDesc['comments'];
        }

        // Extraer comments de AFTER
        if (is_array($afterDesc) && isset($afterDesc['comments']) && is_array($afterDesc['comments'])) {
            $afterComments = $afterDesc['comments'];
        }

        // Si after no tiene comentarios, no hay nada que loguear
        if (empty($afterComments)) {
            return [];
        }

        // 🔹 Normalizar comentarios de "before" para comparar (clave: date|user|content)
        $seen = [];
        foreach ($beforeComments as $c) {
            if (!is_array($c)) continue;

            $date    = trim((string)($c['date'] ?? ''));
            $user    = trim((string)($c['user'] ?? ''));
            $content = trim((string)($c['content'] ?? ''));

            // si está todo vacío, lo ignoramos
            if ($date === '' && $user === '' && $content === '') continue;

            $key = $date.'|'.$user.'|'.$content;
            $seen[$key] = true;
        }

        $lines = [];

        // 🔹 Buscar comentarios que están en "after" y no estaban en "before"
        foreach ($afterComments as $c) {
            if (!is_array($c)) continue;

            $date    = trim((string)($c['date'] ?? ''));
            $user    = trim((string)($c['user'] ?? ''));
            $content = trim((string)($c['content'] ?? ''));

            if ($date === '' && $user === '' && $content === '') continue;

            $key = $date.'|'.$user.'|'.$content;

            if (isset($seen[$key])) {
                // Ya existía antes → no es nuevo
                continue;
            }

            if ($content === '') {
                // no tiene texto, no vale la pena mostrarlo
                continue;
            }

            // Línea simple y clara
            $lines[] = 'Agregó el comentario: "'.$content.'"';
        }

        return $lines;
    }

    /** -----------------------------------------------------------
     *  CONSTRUCCIÓN DE DESCRIPCIÓN DE LOGS
     *  ----------------------------------------------------------- */
    protected function buildDescriptionFromUserLogRow($row, array $columnMap): array
    {
        $detRaw = $this->decodeJson($row->details ?? null);
        $before = isset($detRaw['before']) && is_array($detRaw['before']) ? $detRaw['before'] : [];
        $after  = isset($detRaw['after'])  && is_array($detRaw['after'])  ? $detRaw['after']  : [];

        $lines = [];
        $skip  = $this->diffSkipKeys();

        $modelName = $this->resolveModelName((int)($row->model_id ?? 0)) ?? null;
        $modelKey  = $this->normalizeModelKey($modelName);
        $human     = $this->humanModelLabel($modelName);

        $eventName = strtolower($this->resolveEventName((int)($row->event_id ?? 0)) ?: '');

        //  A) Formato especial para AUTH (login success / failed)
        if ($modelKey === 'auth') {
            $who       = $this->resolveUserName($row->user_id ?? null);
            $attempted = $detRaw['attempted'] ?? null;

            // Detectar éxito/fracaso con tolerancia
            $isSuccess = str_contains($eventName, 'login') && str_contains($eventName, 'success');
            $isFailed  = str_contains($eventName, 'login') && (str_contains($eventName, 'failed') || str_contains($eventName, 'fail') || str_contains($eventName, 'error'));

            if ($isSuccess) {
                $lines[] = trim("{$who} inició sesión correctamente".($attempted ? " como {$attempted}" : ''));
                return $lines;
            }

            if ($isFailed) {
                $lines[] = trim("{$who} intentó iniciar sesión".($attempted ? " como {$attempted}" : '')." (fallido)");
                return $lines;
            }

            // Si es otro evento auth no mapeado, caemos a fallback al final
        }

        //  B) Diffs normales (create/update) con BEFORE/AFTER
            if (!empty($before) || !empty($after)) {
            $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

            foreach ($keys as $k) {
                if (in_array($k, $skip, true)) continue;

                // 🔹 TRATAMIENTO ESPECIAL PARA DESCRIPTION (comentarios)
                if ($k === 'description') {
                    $commentLines = $this->buildCommentDiffLines(
                        $before[$k] ?? null,
                        $after[$k] ?? null
                    );

                    if (!empty($commentLines)) {
                        // Sumamos las líneas de comentarios nuevos a las líneas del log
                        $lines = array_merge($lines, $commentLines);
                    }

                    // No queremos que description pase por el diff genérico
                    continue;
                }

                $hasBefore = array_key_exists($k, $before);
                $hasAfter  = array_key_exists($k, $after);

                if ($hasBefore && $hasAfter) {
                    $v1 = $before[$k];
                    $v2 = $after[$k];
                    $n1 = $this->normalizeForCompare($v1);
                    $n2 = $this->normalizeForCompare($v2);
                    if ($n1 === $n2) continue;

                    $label = $this->labelFor($k, $columnMap, $modelName);
                    $label = str_replace(' (ID)', '', $label);

                    $lines[] = sprintf(
                        'Cambió %s de %s a %s',
                        $label,
                        $this->prettyValueForDiff($k, $v1),
                        $this->prettyValueForDiff($k, $v2)
                    );
                    continue;
                }

                if (!$hasBefore && $hasAfter) {
                    // Para casos: limitamos a coreCase para no ensuciar con campos no relevantes
                    $coreCase = [
                        'state','code','priority_id','created_at','agreement_id','property_address',
                        'inspection_date','document_signing_date','complaint_date','collection_date',
                        'budget_sending_date','settlement_report_date','probable_payment_date','online_collection_date',
                        'accident_number','bank_service_number','advisory_amount','accident_type_id','is_duplicated',
                        'date_of_loss','property_type','customer_id','commune_id','bank_id','insurer_id','loss_adjuster_id',
                        'signature_status','denounce_status','scheduling_status','visit_status','budget_status',
                        'decision_status','payment_status','overall_status','amount_owed','amount_paid','amount_owed_including_vat',
                        'schedule_message_sent','schedule_message_confirmed','schedule_inspection_time','sent_to_acepta'
                    ];

                    if ($modelKey !== 'cases' || in_array($k, $coreCase, true)) {
                        $v2 = $after[$k];
                        $label = $this->labelFor($k, $columnMap, $modelName);
                        $label = str_replace(' (ID)', '', $label);

                        $lines[] = 'Cambió '.$label.' de ---- a '.$this->prettyValueForDiff($k, $v2);
                    }
                }
            }

            if (!empty($lines)) {
                return $lines;
            }
        }

       // C) DELETE con solo BEFORE  →  "Eliminó {humano} id {id}"
        if (empty($lines) && !empty($before) && empty($after) && $this->isDeleteEvent((int)$row->event_id)) {
            $id   = $before['id'] ?? $before['entity_id'] ?? null; // 👈 añade entity_id
            $code = $before['code'] ?? null;

            if ($id) {
                $lines[] = "Eliminó {$human} id {$id}";
            } elseif ($code) {
                $lines[] = "Eliminó {$human} {$code}";
            } else {
                $lines[] = "Eliminó {$human}";
            }
            return $lines;
        }

        //  D) Fallback informativo
       $entityId = $before['id'] ?? $after['id'] ?? $detRaw['id']
         ?? ($before['entity_id'] ?? $after['entity_id'] ?? $detRaw['entity_id'] ?? null);


        // Si el evento es un "create" puro sin diffs: "Creó {humano} id X"
        if (str_starts_with($eventName, 'create') || $eventName === 'created') {
            if ($entityId) {
                $lines[] = "Creó {$human} id {$entityId}";
                return $lines;
            }
            $lines[] = "Creó {$human}";
            return $lines;
        }

        // Último recurso totalmente genérico
        $lines[] = 'Evento: '.$eventName.'  Entidad: '.$human.'  ID: '.$this->humanValue($entityId);

        if (isset($detRaw['attempted'])) $lines[] = 'Intento: '.$this->humanValue($detRaw['attempted']);
        if (isset($detRaw['result']))    $lines[] = 'Resultado: '.$this->humanValue($detRaw['result']);

        return $lines;
    }
}
