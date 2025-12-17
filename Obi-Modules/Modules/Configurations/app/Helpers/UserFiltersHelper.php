<?php

namespace Modules\Configurations\app\Helpers;

use Illuminate\Support\Collection;
class UserFiltersHelper
{
    // Pasos válidos en la máquina de estados.
    public const STEPS = [
        'denuncio',
        'programacion',
        'visita',
        'presupuesto',
        'liquidacion',
        'recaudacion',
    ];

    // NORMALIZA EL NOMBRE DEL PASO
    public static function normalizeStep(?string $step): ?string
    {
        if ($step === null) {
            return null;
        }

        $s = mb_strtolower(trim($step), 'UTF-8');
        return in_array($s, self::STEPS, true) ? $s : null;
    }

    // DEVUELVE DEFAULT + MANAGED CASES POR PASO
    public static function defaultsForStep(string $step): array
    {
        $step = self::normalizeStep($step);

        if (! $step) {
            return [
                'default'       => [],
                'managed_cases' => ['months' => 12, 'target_step' => null],
            ];
        }

        switch ($step) {
            case 'denuncio':
                return [
                    'default' => [
                        'code', 'state', 'customer_name', 'customer_id', 'phone',
                        'agent_name', 'is_duplicated', 'created_at', 'bank_name',
                        'commune_name', 'accident_type_name', 'document_signing_date',
                        'active_notifications', 'case_flow_last_json',
                    ],
                    'managed_cases' => ['months' => 12, 'target_step' => 'programacion'],
                ];

            case 'programacion':
                return [
                    'default' => [
                        'code', 'state', 'customer_name', 'customer_id', 'inspection_date',
                        'schedule_inspection_time', 'consultant_name', 'is_duplicated',
                        'customer_dni', 'complaint_date', 'agent_name',
                        'schedule_message_sent', 'schedule_message_confirmed',
                        'bank_name', 'insurer_name', 'accident_type_name',
                        'accident_number', 'date_of_loss', 'commune_name',
                        'property_address', 'loss_adjuster_name', 'phone',
                        'active_notifications', 'case_flow_last_json',
                    ],
                    'managed_cases' => ['months' => 12, 'target_step' => 'visita'],
                ];

            case 'visita':
                return [
                    'default' => [
                        'inspection_date', 'schedule_inspection_time', 'customer_name',
                        'property_address', 'commune_name', 'phone', 'accident_type_name',
                        'schedule_liquidator_inspector_info', 'loss_adjuster_name',
                        'consultant_name', 'schedule_message_sent',
                        'schedule_message_confirmed',
                    ],
                    'managed_cases' => ['months' => 12, 'target_step' => 'presupuesto'],
                ];

            case 'presupuesto':
                return [
                    'default' => [
                        'code', 'state', 'customer_name', 'customer_id', 'user_name',
                        'document_signing_date', 'budget_sending_date', 'commune_name',
                        'inspection_date', 'loss_adjuster_name', 'accident_number',
                        'accident_type_name', 'active_notifications', 'case_flow_last_json',
                    ],
                    'managed_cases' => ['months' => 12, 'target_step' => 'liquidacion'],
                ];

            case 'liquidacion':
                return [
                    'default' => [
                        'code', 'state', 'customer_name', 'customer_id', 'user_name',
                        'loss_adjuster_name', 'accident_type_name', 'accident_number',
                        'commune_name', 'inspection_date', 'budget_sending_date',
                        'settlement_report_date', 'approved_amount', 'is_duplicated',
                        'active_notifications', 'case_flow_last_json',
                    ],
                    'managed_cases' => ['months' => 12, 'target_step' => 'recaudacion'],
                ];

            case 'recaudacion':
                return [
                    'default' => [
                        'code', 'state', 'customer_name', 'phone', 'customer_id',
                        'settlement_report_date', 'probable_payment_date',
                        'approved_amount', 'advisory_amount', 'amount_owed',
                        'accident_number', 'amount_paid', 'bank_name',
                        'accident_type_name', 'collection_date', 'payment_status',
                        'active_notifications', 'case_flow_last_json',
                    ],
                    'managed_cases' => ['months' => 12, 'target_step' => null],
                ];
        }

        return [
            'default'       => [],
            'managed_cases' => ['months' => 12, 'target_step' => null],
        ];
    }

    // CREA UN STEP VACÍO PARA UN PASO: default + filters + managed_cases
    public static function buildEmptyStepConfig(string $step): array
    {
        $defs = self::defaultsForStep($step);

        return [
            'default'       => $defs['default'],
            'filters'       => [],
            'managed_cases' => $defs['managed_cases'],
        ];
    }

    // GENERA KEY LIMPIA DESDE EL NOMBRE DEL FILTRO
    public static function makeKeyFromName(string $name): string
    {
        $k = mb_strtolower(trim($name), 'UTF-8');
        $k = preg_replace('/[^a-z0-9]+/u', '_', $k);
        $k = preg_replace('/_+/', '_', $k);
        $k = trim($k, '_');

        if ($k === '') {
            $k = 'filtro_' . substr(sha1($name), 0, 8);
        }

        return $k;
    }

    // NORMALIZA CONTENT → SI ES STRING LO DECODIFICA, SI NO, ARRAY
     public static function normalizeConfigContent(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            // Primero intento decodificar tal cual
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            // Si venía doblemente encodeado (con comillas alrededor), intento quitar comillas externas
            $trimmed = trim($raw);
            if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
                $trimmed = substr($trimmed, 1, -1);
                $decoded2 = json_decode(stripslashes($trimmed), true);
                if (is_array($decoded2)) {
                    return $decoded2;
                }
            }

            return [];
        }

        return (array) $raw;
    }

    // BUSCA LA FILA DE BD DEL USUARIO (STRICT: 1 fila = 1 usuario)
    // Evita la fila legacy donde vienen muchos usuarios dentro del mismo content.
    public static function findRowForUser(Collection $rows, int $userId): ?array
    {
        $userKey = (string) $userId;

        foreach ($rows as $row) {
            $content = self::normalizeConfigContent($row->content ?? '[]');

            // Debe ser array, tener SOLO 1 key raíz, y esa key debe ser el usuario
            if (! is_array($content)) {
                continue;
            }

            if (count($content) !== 1) {
                continue; // <- aquí se descarta la mecánica antigua (multiusuario)
            }

            if (! array_key_exists($userKey, $content)) {
                continue;
            }

            return [
                'row'     => $row,
                'content' => $content,
            ];
        }

        return null;
    }

    // GARANTIZA QUE EXISTE LA ESTRUCTURA: user → steps → step
    // (USADO POR STORE, UPDATE, DESTROY)
     public static function ensureUserStepStructure(array $content, int $userId, string $step): array
    {
        $userKey = (string) $userId;
        $step    = self::normalizeStep($step);

        if (! $step) {
            return $content;
        }

        // Si el usuario no existe todavía en content, lo creamos vacío
        if (! isset($content[$userKey])) {
            $content[$userKey] = [
                'steps' => [],
            ];
        }

        // Si el paso no existe, lo creamos desde cero
        if (! isset($content[$userKey]['steps'][$step])) {
            $content[$userKey]['steps'][$step] = self::buildEmptyStepConfig($step);
            return $content;
        }

        // Si existe, tomamos filtros existentes (si los hay)
        $existing = $content[$userKey]['steps'][$step];
        $defs     = self::defaultsForStep($step);

        $filters = [];
        if (isset($existing['filters']) && is_array($existing['filters'])) {
            $filters = array_values($existing['filters']);
        }

        // RECONSTRUIMOS el bloque del paso en el orden que tú quieres:
        // default → filters → managed_cases
        $content[$userKey]['steps'][$step] = [
            'default'       => $defs['default'],
            'filters'       => $filters,
            'managed_cases' => $defs['managed_cases'],
        ];

        return $content;
    }
}
