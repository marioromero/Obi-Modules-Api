<?php

namespace Modules\Configurations\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationSeeder extends Seeder
{

    public function run(): void
    {

        // 1) Generar los arrays de IDs para países y provincias

        $countryIds  = range(1, 85);  // [1, 2, …, 85]
        $provinceIds = range(1, 56);  // [1, 2, …, 56]

        // 2) Actualizar (o insertar) la configuración de Global_geography (type_id = 2)
        //    El content ahora contendrá dos arrays anidados: 'countries' y 'provinces'

        DB::connection('configurations_db')->table('configurations')->updateOrInsert(
            ['type_id' => 2],
            ['content' => json_encode([
                'countries' => $countryIds,
                'provinces'=> $provinceIds,
            ], JSON_UNESCAPED_UNICODE)]
        );

        // 3) Definir e insertar la configuración de Global_settings (type_id = 3)
        //    Aquí metemos un array de textos con los posibles estados civiles

        $maritalStatuses = [
            'Casada', 'Casado', 'Conviviente Civil',
            'Divorciada', 'Divorciado',
            'Separada', 'Separado',
            'Soltera', 'Soltero',
            'Unión Civil',
            'Viuda', 'Viudo'
        ];

        DB::connection('configurations_db')->table('configurations')->updateOrInsert(
            ['type_id' => 3],
            ['content' => json_encode([
                'marital_statuses' => $maritalStatuses,
            ], JSON_UNESCAPED_UNICODE)]
        );

        // 4) Definir e insertar la configuración de User_responsabilities (type_id = 4)
        //    Mapea cada estado general de caso a su array de usuarios encargados
        $userResponsibilities = [
            'Denuncio'      => ['user_assigned' => [27]],
            'Programacion'  => ['user_assigned' => [28]],
            'Visita'        => ['user_assigned' => [21, 22]],
            'Presupuesto'   => ['user_assigned' => [21]],
            'Liquidacion'   => ['user_assigned' => [21]],
            'Recaudacion'   => ['user_assigned' => [27]],
        ];

        DB::connection('configurations_db')->table('configurations')->updateOrInsert(
            ['type_id' => 4],
            ['content' => json_encode($userResponsibilities, JSON_UNESCAPED_UNICODE)]
        );

        // 5) Columns_by_rol
        $columnsByRolTypeId = DB::connection('configurations_db')->table('types')->where('name', 'Columns_by_rol')->value('id');

        if ($columnsByRolTypeId) {
            $columnsByRole = [
                // 1 = Administrador
                '1' => [
                    'code','customer_name','customer_dni','state','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'assigned_user_name','consultant_name','agent_name',
                    'payment_status','amount_owed','amount_paid','advisory_amount',
                    'probable_payment_date','overall_status',
                    'signature_status','created_by_name'
                ],

                // 2 = Ejecutivo
                '2' => [
                    'code','customer_name','customer_dni','state','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'assigned_user_name','signature_status','denounce_status',
                    'scheduling_status','visit_status','budget_status','decision_status',
                    'payment_status','inspection_date','budget_sending_date',
                    'settlement_report_date','contestation_date','probable_payment_date',
                    'accident_number','bank_service_number'
                ],

                // 3 = Coordinador
                '3' => [
                    'code','state','created_at',
                    'property_address','inspection_date','document_signing_date','complaint_date','collection_date',
                    'budget_sending_date','settlement_report_date','probable_payment_date',
                    'customer_name','customer_dni','customer_address','customer_commune_name',
                    'bank_name','assigned_user_name','accident_type_name','agent_name',
                    'commune_name','loss_adjuster_name','insurer_name',
                ],

                // 4 = Administrativo
                '4' => [
                    'code','state',
                    'customer_name','customer_dni','property_address','property_type','created_at',
                    'accident_type_name','bank_name','agent_name','document_signing_date','agreement_name',
                    'bank_service_number','complaint_date','accident_number','is_duplicated',
                    'insurer_name','loss_adjuster_name','inspection_date','budget_sending_date',
                    'settlement_report_date','probable_payment_date','collection_date',
                    'amount_owed','amount_paid','online_collection_date',
                ],

                // 5 = Asesor
                '5' => [
                    'code','customer_name','customer_dni','state','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','consultant_id', 'consultant_name', 'approved_amount',
                    'inspection_date','visit_status','budget_status','decision_status',
                    'settlement_report_date','date_of_loss','contestation_date',
                    'insurer_name','loss_adjuster_name','property_type',
                    'uf_approved','advisory_amount'
                ],
            ];

            DB::connection('configurations_db')->table('configurations')->updateOrInsert(
                ['type_id' => $columnsByRolTypeId],
                ['content' => json_encode($columnsByRole, JSON_UNESCAPED_UNICODE)]
            );
        }

        //Seeder de filtros por usuario en este caso solamente el 22 (Edilia)
        $userId  = 22;

        $userKey = (string) $userId;

        $newConfigColumns = [
            [
                'key'     => 'configuration_1',
            'name'    => 'Estado en proceso sin firmas',
            'columns' => ['code','customer_name','created_at','state','bank_name','commune_name','accident_type_name'],
            // Antiguos primero: created_at ASC
            'sql'     => "WHERE overall_status <> 'cerrado' AND document_signing_date IS NULL AND signature_status <> 'firmados' ORDER BY created_at ASC, id ASC",
        ],
        [
            'key'     => 'configuration_2',
            'name'    => 'Sin denuncio y contrato firmado',
            'columns' => ['code','customer_name','state','accident_type_name','bank_name','commune_name','document_signing_date'],
            // Ambos documentos firmados (canónico o respaldo) y sin denuncio. Orden: fecha efectiva de firma ASC.
            'sql'     => "WHERE complaint_date IS NULL AND (document_signing_date IS NOT NULL OR signature_status = 'firmados' OR (fecha_firma_contrato IS NOT NULL AND fecha_firma_mandato IS NOT NULL)) ORDER BY COALESCE(document_signing_date, GREATEST(fecha_firma_contrato, fecha_firma_mandato)) ASC, id ASC",
        ],
        [
            'key'     => 'configuration_3',
            'name'    => 'Sin pago y con informe de liquidación',
            'columns' => ['code','customer_name','state','settlement_report_date','approved_amount','advisory_amount','amount_owed'],
            // Liquidado y sin pago (excluye pagados y parciales). Orden: settlement_report_date ASC.
            'sql'     => "WHERE settlement_report_date IS NOT NULL AND (amount_paid IS NULL OR amount_paid = 0) AND (payment_status IS NULL OR payment_status NOT IN ('pagado','parcialmente pagado')) ORDER BY settlement_report_date ASC, id ASC",
        ],
        [
            'key'     => 'configuration_4',
            'name'    => 'Con fecha probable de pago y sin cobranza',
            'columns' => ['code','customer_name','state','settlement_report_date','probable_payment_date','approved_amount','advisory_amount','bank_name','accident_type_name'],
            // Tiene fecha probable, sin cobranza (incluye online), excluye pagados. Orden: probable_payment_date ASC.
            'sql'     => "WHERE probable_payment_date IS NOT NULL AND collection_date IS NULL AND online_collection_date IS NULL AND (payment_status IS NULL OR payment_status NOT IN ('pagado','cobranza','cobranza online')) ORDER BY probable_payment_date ASC, id ASC",
        ],
        [
            'key'     => 'configuration_5',
            'name'    => 'En cobranza sin pago',
            'columns' => ['code','customer_name','state','approved_amount','advisory_amount','probable_payment_date','collection_date','payment_status'],
            // Señales de cobranza (fecha u estado), sin pago real, excluye pagados y parciales. Orden: fecha de cobranza disponible ASC.
            'sql'     => "WHERE (collection_date IS NOT NULL OR online_collection_date IS NOT NULL OR payment_status IN ('cobranza','cobranza online')) AND (amount_paid IS NULL OR amount_paid = 0) AND (payment_status IS NULL OR payment_status NOT IN ('pagado','parcialmente pagado')) ORDER BY COALESCE(collection_date, online_collection_date) ASC, id ASC",
        ],
    ];

            // 1) Leer content existente
        $existing = [];
        if ($row = DB::connection('configurations_db')->table('configurations')->where('type_id', 1)->first()) {
            $existing = json_decode($row->content ?? '[]', true) ?: [];
        }

        // 2) Asegurar llaves mínimas y asignar la nueva lista SOLO para el user 22
        $existing[$userKey] = $existing[$userKey] ?? [];
        $existing[$userKey]['filters'] = $existing[$userKey]['filters'] ?? [];
        $existing[$userKey]['filters']['config_columns'] = $newConfigColumns;

        // 3) Guardar
        DB::connection('configurations_db')->table('configurations')->updateOrInsert(
            ['type_id' => 1], // User_filters
            ['content' => json_encode($existing, JSON_UNESCAPED_UNICODE)]
        );
    }
}
