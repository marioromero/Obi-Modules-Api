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
            'Denuncio'     => ['user_assigned' => [21, 22, 11, 4, 5]],
            'Programacion' => ['user_assigned' => [21, 4, 5, 11, 22]],
            'Visita'       => ['user_assigned' => [21, 5, 4, 11, 22]],
            'Presupuesto'  => ['user_assigned' => [11, 5, 4, 22, 21]],
            'Liquidacion'  => ['user_assigned' => [5, 11, 4, 21, 22]],
            'Recaudacion'  => ['user_assigned' => [21, 4, 5, 11, 22]],
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
                    'code','state','customer_name','customer_id','customer_dni','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'assigned_user_name','consultant_name','agent_name',
                    'payment_status','amount_owed','amount_paid','advisory_amount',
                    'probable_payment_date','overall_status',
                    'signature_status','created_by_name','case_flows_last'
                ],

                // 2 = Ejecutivo
                '2' => [
                    'code','state','customer_name','customer_id','customer_dni','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'assigned_user_name','signature_status','denounce_status',
                    'scheduling_status','visit_status','budget_status','decision_status',
                    'payment_status','inspection_date','budget_sending_date',
                    'settlement_report_date','contestation_date','probable_payment_date',
                    'accident_number','bank_service_number','case_flows_last'
                ],

                // 3 = Coordinador
                '3' => [
                    'code','state','customer_name','customer_id','created_at',
                    'property_address','inspection_date','document_signing_date','complaint_date','collection_date',
                    'budget_sending_date','settlement_report_date','probable_payment_date',
                    'customer_dni','customer_address','customer_commune_name',
                    'bank_name','assigned_user_name','accident_type_name','agent_name',
                    'commune_name','loss_adjuster_name','insurer_name','case_flows_last'
                ],

                // 4 = Administrativo
                '4' => [
                    'code','state',
                    'customer_name','customer_id','customer_dni','property_address','property_type','created_at',
                    'accident_type_name','bank_name','agent_name','document_signing_date','agreement_name',
                    'bank_service_number','complaint_date','accident_number','is_duplicated',
                    'insurer_name','loss_adjuster_name','inspection_date','budget_sending_date',
                    'settlement_report_date','probable_payment_date','collection_date',
                    'amount_owed','amount_paid','online_collection_date','case_flows_last'
                ],

                // 5 = Asesor
                '5' => [
                    'code','state','customer_name','customer_id','customer_dni','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','consultant_id', 'consultant_name', 'approved_amount',
                    'inspection_date','visit_status','budget_status','decision_status',
                    'settlement_report_date','date_of_loss','contestation_date',
                    'insurer_name','loss_adjuster_name','property_type',
                    'uf_approved','advisory_amount','case_flows_last'
                ],
            ];

            DB::connection('configurations_db')->table('configurations')->updateOrInsert(
                ['type_id' => $columnsByRolTypeId],
                ['content' => json_encode($columnsByRole, JSON_UNESCAPED_UNICODE)]
            );
        }

        //Filtros de usuario
        $typeId = DB::connection('configurations_db')->table('types')
            ->where('name', 'User_filters')
            ->value('id');

        if ($typeId) {
            // Leer contenido existente y normalizar
            $row = DB::connection('configurations_db')->table('configurations')
                ->where('type_id', $typeId)->first();
            $content = $row ? (json_decode($row->content ?? '[]', true) ?: []) : [];

            //Usuario 22 Edilia
            $content['22'] = [
                'user_id' => 22,
                'steps' => [
                    'denuncio' => [
                        'default' => [
                            'code','state','customer_name','customer_id','phone','created_at','bank_name','commune_name',
                            'accident_type_name','document_signing_date', 'active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Estado en proceso sin firmas',
                                'color'   => '#ff8878',
                                'columns' => ['code','state','customer_name','customer_id','phone','created_at','bank_name','commune_name','accident_type_name'],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Ingreso'
                                              AND signature_status IN ('generado','enviado a acepta','notificado')
                                              AND document_signing_date IS NULL
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at ASC, id ASC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Sin denuncio y contrato firmado',
                                'color'   => '#ec81ff',
                                'columns' => [
                                    'code','state','customer_name','customer_id','accident_type_name',
                                    'bank_name','commune_name','document_signing_date'
                                ],
                                 'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Denuncio'
                                              AND complaint_date IS NULL
                                              AND (
                                                    (fecha_firma_contrato IS NOT NULL AND fecha_firma_mandato IS NOT NULL)
                                                    OR (
                                                         fecha_firma_contrato IS NULL
                                                     AND fecha_firma_mandato  IS NULL
                                                     AND LOWER(signature_status) = 'firmados'
                                                    )
                                                  )
                                              AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                              ORDER BY COALESCE(document_signing_date,
                                                                GREATEST(fecha_firma_contrato, fecha_firma_mandato)) ASC,
                                                       id ASC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Solo un documento firmado',
                                'color'   => '#ffa94d',
                                'columns' => [
                                    'code','state','customer_name','customer_id','created_at','bank_name','commune_name','accident_type_name','signature_status'
                                ],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Ingreso'
                                              AND signature_status IN ('contrato pendiente','mandato pendiente')
                                              AND document_signing_date IS NULL
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at ASC, id ASC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'programacion'],
                    ],
                    'recaudacion' => [
                        'default' => [
                            'code','state','customer_name','customer_id','settlement_report_date','probable_payment_date',
                            'approved_amount','advisory_amount','amount_owed',
                            'bank_name','accident_type_name','collection_date','payment_status', 'active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                        [
                            'key'     => 'configuration_1',
                            'name'    => 'En cobranza sin pago',
                            'color'   => '#bdff81',
                            'columns' => ['code','state','customer_name','customer_id','approved_amount','advisory_amount','probable_payment_date','collection_date','payment_status'],
                            'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Recaudacion'
                                          AND LOWER(payment_status) = 'cobranza'
                                          AND collection_date IS NOT NULL
                                          AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                          ORDER BY COALESCE(collection_date, settlement_report_date, created_at) ASC, id ASC",
                        ],
                        [
                            'key'     => 'configuration_2',
                            'name'    => 'Sin fecha probable de pago y con informe de liquidación',
                            'color'   => '#818bff',
                            'columns' => ['code','state','customer_name','customer_id','settlement_report_date','approved_amount','advisory_amount','amount_owed'],
                            'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Recaudacion'
                                          AND settlement_report_date IS NOT NULL
                                          AND probable_payment_date IS NULL
                                          AND LOWER(payment_status) IN ('pendiente','cobranza')
                                          AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                        ORDER BY settlement_report_date ASC, id ASC",
                        ],
                        [
                            'key'     => 'configuration_3',
                            'name'    => 'Con fecha de pago y sin cobranza',
                            'color'   => '#81ffe3',
                            'columns' => ['code','state','customer_name','customer_id','probable_payment_date','settlement_report_date','probable_payment_date','approved_amount','advisory_amount','bank_name','accident_type_name'],
                            'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Recaudacion'
                                          AND probable_payment_date IS NOT NULL
                                          AND collection_date IS NULL
                                          AND LOWER(payment_status) IN ('pendiente','cobranza')
                                         AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                         ORDER BY probable_payment_date ASC, id ASC",
                        ],
                    ],
                        'managed_cases' => ['months' => 2, 'target_step' => null],
                    ],
                ],
            ];

            // Usuario 21 Beatriz
            $content['21'] = [
                'user_id' => 21,
                'steps' => [
                    'denuncio'    => $content['22']['steps']['denuncio'],
                    'recaudacion' => $content['22']['steps']['recaudacion'],
                    'programacion' => [
                        'default' => [
                            'code','state','customer_name','customer_id','is_duplicated','customer_dni','bank_name','insurer_name',
                            'accident_type_name','accident_number','date_of_loss','commune_name',
                            'property_address','loss_adjuster_name','phone','inspection_date', 'active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Por asesor: Omar Carrasco',
                                'color'   => '#4f86ff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 5
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Por asesor: Ivette Contreras',
                                'color'   => '#b36bff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 23
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Por asesor: Pablo Yañez',
                                'color'   => '#18c29c',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 11
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_4',
                                'name'    => 'Denuncio realizado sin fecha de visita',
                                'color'   => '#ff6d6d',
                                'columns' => [
                                    'code','state','customer_name','customer_id','is_duplicated','commune_name','bank_name',
                                    'accident_type_name','complaint_date','inspection_date','bank_service_number'
                                ],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND complaint_date IS NOT NULL
                                              AND inspection_date IS NULL
                                              AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                              ORDER BY complaint_date ASC, id ASC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'visita'],
                    ],
                    'visita' => [
                        'default' => [
                            'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                            'accident_type_name','accident_number','date_of_loss','commune_name',
                            'property_address','loss_adjuster_name','phone','inspection_date', 'active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Por asesor: Omar Carrasco',
                                'color'   => '#4f86ff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 5
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Por asesor: Ivette Contreras',
                                'color'   => '#b36bff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 23
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Por asesor: Pablo Yañez',
                                'color'   => '#18c29c',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 11
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                             ORDER BY created_at DESC, id DESC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'presupuesto'],
                    ],
                ],
            ];

            // Usuario 11 Pablo
            $content['11'] = [
                'user_id' => 11,
                'steps' => [
                    // 1) Denuncio
                    'denuncio' => $content['22']['steps']['denuncio'],
                    // 2) Programación
                    'programacion' => [
                        'default' => [
                            'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                            'accident_type_name','accident_number','date_of_loss','commune_name',
                            'property_address','loss_adjuster_name','phone','inspection_date','active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Por asesor: Pablo Yañez',
                                'color'   => '#18c29c',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 11
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Por asesor: Omar Carrasco',
                                'color'   => '#4f86ff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 5
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Por asesor: Ivette Contreras',
                                'color'   => '#b36bff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 23
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'visita'],
                    ],
                    'visita' => [
                        'default' => [
                            'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                            'accident_type_name','accident_number','date_of_loss','commune_name',
                            'property_address','loss_adjuster_name','phone','inspection_date','active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Por asesor: Pablo Yañez',
                                'color'   => '#18c29c',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 11
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Por asesor: Omar Carrasco',
                                'color'   => '#4f86ff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 5
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Por asesor: Ivette Contreras',
                                'color'   => '#b36bff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 23
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'presupuesto'],
                    ],
                    // 4) Presupuesto
                    'presupuesto' => [
                        'default' => [
                            'code','state','customer_name','customer_id','user_name','document_signing_date','budget_sending_date','is_duplicated',
                            'commune_name','inspection_date','loss_adjuster_name','accident_number','accident_type_name', 'active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Sin presupuesto enviado y con fecha de visita',
                                'color'   => '#ffb74d',
                                'columns' => ['code','state','customer_name','customer_id','agent_name','document_signing_date',
                                             'budget_sending_date','is_duplicated','commune_name','inspection_date',
                                             'loss_adjuster_name','accident_number','accident_type_name'],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Presupuesto'
                                              AND budget_sending_date IS NULL
                                              AND inspection_date IS NOT NULL
                                              AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                              ORDER BY inspection_date ASC, id ASC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'liquidacion'],
                    ],
                    // 5) Liquidación
                    'liquidacion' => [
                        'default' => [
                            'code','state','customer_name','customer_id','user_name','loss_adjuster_name','accident_type_name',
                            'accident_number','commune_name','inspection_date','budget_sending_date',
                            'settlement_report_date','approved_amount','is_duplicated', 'active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Sin fecha de liquidación',
                                'color'   => '#64b5f6',
                                'columns' => ['code','state','customer_name','customer_id','loss_adjuster_name','accident_type_name',
                                             'accident_number','commune_name','inspection_date','budget_sending_date',
                                             'settlement_report_date','approved_amount','is_duplicated'],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Liquidacion'
                                              AND settlement_report_date IS NULL
                                              AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                              ORDER BY created_at ASC, id ASC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'recaudacion'],
                    ],
                    // 6) Recaudación
                    'recaudacion' => $content['22']['steps']['recaudacion'],
                ],
            ];

            // Usuario 5 Omar
            $content['5'] = [
                'user_id' => 5,
                'steps' => [
                    'denuncio'    => $content['22']['steps']['denuncio'],
                    'recaudacion' => $content['22']['steps']['recaudacion'],

                    'programacion' => [
                        'default' => [
                            'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                            'accident_type_name','accident_number','date_of_loss','commune_name',
                            'property_address','loss_adjuster_name','phone','inspection_date','active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Por asesor: Omar Carrasco',
                                'color'   => '#4f86ff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 5
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Por asesor: Ivette Contreras',
                                'color'   => '#b36bff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 23
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Por asesor: Pablo Yañez',
                                'color'   => '#18c29c',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 11
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                              AND scheduling_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'visita'],
                    ],

                    'visita' => [
                        'default' => [
                            'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                            'accident_type_name','accident_number','date_of_loss','commune_name',
                            'property_address','loss_adjuster_name','phone','inspection_date','active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Por asesor: Omar Carrasco',
                                'color'   => '#4f86ff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 5
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_2',
                                'name'    => 'Por asesor: Ivette Contreras',
                                'color'   => '#b36bff',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 23
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                            [
                                'key'     => 'configuration_3',
                                'name'    => 'Por asesor: Pablo Yañez',
                                'color'   => '#18c29c',
                                'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                              'accident_type_name','accident_number','date_of_loss','commune_name',
                                              'property_address','loss_adjuster_name','phone','inspection_date',
                                              'consultant_name'],
                                'sql'     => "WHERE consultant_id = 11
                                              AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                              AND visit_status IN ('pendiente','en proceso')
                                              AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                              ORDER BY created_at DESC, id DESC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'presupuesto'],
                    ],
                    'presupuesto' => [
                        'default' => [
                            'code','state','customer_name','customer_id','user_name','document_signing_date','budget_sending_date',
                            'commune_name','inspection_date','loss_adjuster_name','accident_number','accident_type_name','active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Sin presupuesto enviado y con fecha de visita',
                                'color'   => '#ffb74d',
                                'columns' => ['code','state','customer_name','customer_id','user_name','inspection_date','budget_sending_date','commune_name','loss_adjuster_name','accident_number','accident_type_name'],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Presupuesto'
                                              AND budget_sending_date IS NULL
                                              AND inspection_date IS NOT NULL
                                              AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                              ORDER BY inspection_date ASC, id ASC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'liquidacion'],
                    ],

                    'liquidacion' => [
                        'default' => [
                            'code','state','customer_name','customer_id','user_name','loss_adjuster_name','accident_type_name',
                            'accident_number','commune_name','inspection_date','budget_sending_date',
                            'settlement_report_date','approved_amount','is_duplicated','active_notifications', 'case_flow_last_json'
                        ],
                        'filters' => [
                            [
                                'key'     => 'configuration_1',
                                'name'    => 'Sin fecha de liquidación',
                                'color'   => '#64b5f6',
                                'columns' => ['code','state','customer_name','customer_id','user_name','loss_adjuster_name','accident_type_name','accident_number','commune_name','inspection_date','budget_sending_date','settlement_report_date','approved_amount','is_duplicated'],
                                'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Liquidacion'
                                              AND settlement_report_date IS NULL
                                              AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                              ORDER BY created_at ASC, id ASC",
                            ],
                        ],
                        'managed_cases' => ['months' => 2, 'target_step' => 'recaudacion'],
                    ],
                ],
            ];

            // Usuario 4 Nicole
            $content['4'] = [
                'user_id' => 4,
                 'steps' => [
                'denuncio'    => $content['22']['steps']['denuncio'],

                'programacion' => [
                    'default' => [
                        'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                        'accident_type_name','accident_number','date_of_loss','commune_name',
                        'property_address','loss_adjuster_name','phone','inspection_date','active_notifications', 'case_flow_last_json'
                    ],
                    'filters' => [
                        [
                            'key'     => 'configuration_1',
                            'name'    => 'Por asesor: Omar Carrasco',
                            'color'   => '#4f86ff',
                            'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                          'accident_type_name','accident_number','date_of_loss','commune_name',
                                          'property_address','loss_adjuster_name','phone','inspection_date',
                                          'consultant_name'],
                            'sql'     => "WHERE consultant_id = 5
                                          AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                          AND scheduling_status IN ('pendiente','en proceso')
                                          AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                          ORDER BY created_at DESC, id DESC",
                        ],
                        [
                            'key'     => 'configuration_2',
                            'name'    => 'Por asesor: Ivette Contreras',
                            'color'   => '#b36bff',
                            'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                          'accident_type_name','accident_number','date_of_loss','commune_name',
                                          'property_address','loss_adjuster_name','phone','inspection_date',
                                          'consultant_name'],
                            'sql'     => "WHERE consultant_id = 23
                                          AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                          AND scheduling_status IN ('pendiente','en proceso')
                                          AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                          ORDER BY created_at DESC, id DESC",
                        ],
                        [
                            'key'     => 'configuration_3',
                            'name'    => 'Por asesor: Pablo Yañez',
                            'color'   => '#18c29c',
                            'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                          'accident_type_name','accident_number','date_of_loss','commune_name',
                                          'property_address','loss_adjuster_name','phone','inspection_date',
                                          'consultant_name'],
                            'sql'     => "WHERE consultant_id = 11
                                          AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Programacion'
                                          AND scheduling_status IN ('pendiente','en proceso')
                                          AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                          ORDER BY created_at DESC, id DESC",
                        ],
                    ],
                    'managed_cases' => ['months' => 2, 'target_step' => 'visita'],
                ],

                'visita' => [
                    'default' => [
                        'code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                        'accident_type_name','accident_number','date_of_loss','commune_name',
                        'property_address','loss_adjuster_name','phone','inspection_date','active_notifications', 'case_flow_last_json'
                    ],
                    'filters' => [
                        [
                            'key'     => 'configuration_1',
                            'name'    => 'Por asesor: Omar Carrasco',
                            'color'   => '#4f86ff',
                            'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                          'accident_type_name','accident_number','date_of_loss','commune_name',
                                          'property_address','loss_adjuster_name','phone','inspection_date',
                                          'consultant_name'],
                            'sql'     => "WHERE consultant_id = 5
                                          AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                          AND visit_status IN ('pendiente','en proceso')
                                          AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                          ORDER BY created_at DESC, id DESC",
                        ],
                        [
                            'key'     => 'configuration_2',
                            'name'    => 'Por asesor: Ivette Contreras',
                            'color'   => '#b36bff',
                            'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                          'accident_type_name','accident_number','date_of_loss','commune_name',
                                          'property_address','loss_adjuster_name','phone','inspection_date',
                                          'consultant_name'],
                            'sql'     => "WHERE consultant_id = 23
                                          AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                          AND visit_status IN ('pendiente','en proceso')
                                          AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                          ORDER BY created_at DESC, id DESC",
                        ],
                        [
                            'key'     => 'configuration_3',
                            'name'    => 'Por asesor: Pablo Yañez',
                            'color'   => '#18c29c',
                            'columns' => ['code','state','customer_name','customer_id','customer_dni','bank_name','insurer_name',
                                          'accident_type_name','accident_number','date_of_loss','commune_name',
                                          'property_address','loss_adjuster_name','phone','inspection_date',
                                          'consultant_name'],
                            'sql'     => "WHERE consultant_id = 11
                                          AND SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Visita'
                                          AND visit_status IN ('pendiente','en proceso')
                                          AND (overall_status IS NULL OR overall_status <> 'cerrado')
                                          ORDER BY created_at DESC, id DESC",
                        ],
                    ],
                    'managed_cases' => ['months' => 2, 'target_step' => 'presupuesto'],
                ],

                'presupuesto' => [
                    'default' => [
                        'code','state','customer_name','customer_id','user_name','document_signing_date','budget_sending_date',
                        'commune_name','inspection_date','loss_adjuster_name','accident_number','accident_type_name','active_notifications', 'case_flow_last_json'
                    ],
                    'filters' => [
                        [
                            'key'     => 'configuration_1',
                            'name'    => 'Sin presupuesto enviado y con fecha de visita',
                            'color'   => '#ffb74d',
                            'columns' => ['code','state','customer_name','customer_id','user_name','inspection_date','budget_sending_date','commune_name','loss_adjuster_name','accident_number','accident_type_name'],
                            'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Presupuesto'
                                          AND budget_sending_date IS NULL
                                          AND inspection_date IS NOT NULL
                                          AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                          ORDER BY inspection_date ASC, id ASC",
                        ],
                    ],
                    'managed_cases' => ['months' => 2, 'target_step' => 'liquidacion'],
                ],

                'liquidacion' => [
                    'default' => [
                        'code','state','customer_name','customer_id','user_name','loss_adjuster_name','accident_type_name',
                        'accident_number','commune_name','inspection_date','budget_sending_date',
                        'settlement_report_date','approved_amount','is_duplicated','active_notifications', 'case_flow_last_json'
                    ],
                    'filters' => [
                        [
                            'key'     => 'configuration_1',
                            'name'    => 'Sin fecha de liquidación',
                            'color'   => '#64b5f6',
                            'columns' => ['code','state','customer_name','customer_id','user_name','loss_adjuster_name','accident_type_name','accident_number','commune_name','inspection_date','budget_sending_date','settlement_report_date','approved_amount','is_duplicated'],
                            'sql'     => "WHERE SUBSTRING_INDEX(REPLACE(state,'\\\\','/'), '/', -1) = 'Liquidacion'
                                          AND settlement_report_date IS NULL
                                          AND (overall_status IS NULL OR LOWER(overall_status) <> 'cerrado')
                                          ORDER BY created_at ASC, id ASC",
                        ],
                    ],
                    'managed_cases' => ['months' => 2, 'target_step' => 'recaudacion'],
                ],

                 'recaudacion' => [
                    'default' => [
                        'code','state','customer_name','customer_id',
                        'settlement_report_date','probable_payment_date',
                        'approved_amount','advisory_amount','amount_owed',
                        'bank_name','accident_type_name','collection_date',
                        'payment_status','document_signing_date','agent_name',
                        'commune_name','phone','created_at',
                        'active_notifications','case_flow_last_json'
                    ],
                    'filters' => [
                        [
                            'key'     => 'configuration_1',
                            'name'    => 'Cierre de mes',
                            'color'   => '#f48fb1',
                            'columns' => ['customer_name','customer_id','document_signing_date','agent_name'],
                            'sql'     => "WHERE document_signing_date >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
                                          AND document_signing_date <  DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                          ORDER BY document_signing_date ASC, id ASC",
                        ],
                        [
                            'key'     => 'configuration_2',
                            'name'    => 'Filtro para duplicados',
                            'color'   => '#ffb3b3',
                            'columns' => [
                                'code','state','customer_name','customer_id',
                                'commune_name','phone','created_at','agent_name','accident_type_name'
                            ],
                            'sql'     => "ORDER BY created_at DESC, id DESC",
                        ],
                    ],
                    'managed_cases' => ['months' => 2, 'target_step' => 'recaudacion'],
                ],
            ],
        ];

            // Guardar
            DB::connection('configurations_db')->table('configurations')->updateOrInsert(
                ['type_id' => $typeId],
                ['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]
            );
        }

        // 6) States_machine (type_id = 6)
        DB::connection('configurations_db')->table('configurations')->updateOrInsert(
            ['type_id' => 6],
            ['content' => json_encode([
                "connection" => "cases_db",
                "module" => "Cases",
                "model" => "CaseEntity",
                "table" => "cases",
                "namespace" => "Traro",
                "default" => "Ingreso",
                "states" => [
                    "Ingreso",
                    "Denuncio",
                    "Programacion",
                    "Visita",
                    "Presupuesto",
                    "Liquidacion",
                    "Recaudacion",
                    "Cancelado",
                    "Desistido",
                    "DesistidoSinVisita"
                ],
                "transitions" => [
                    "Ingreso" => ["Denuncio", "Cancelado", "Desistido", "DesistidoSinVisita"],
                    "Denuncio" => ["Programacion", "Ingreso", "Cancelado", "Desistido", "DesistidoSinVisita"],
                    "Programacion" => ["Visita", "Denuncio", "Cancelado", "Desistido", "DesistidoSinVisita"],
                    "Visita" => ["Presupuesto", "Programacion", "Cancelado", "Desistido"],
                    "Presupuesto" => ["Liquidacion", "Visita", "Cancelado", "Desistido"],
                    "Liquidacion" => ["Recaudacion", "Presupuesto", "Cancelado", "Desistido"],
                    "Recaudacion" => ["Cancelado", "Desistido"],
                    "Cancelado" => ["Ingreso", "Denuncio", "Programacion", "Visita", "Presupuesto", "Liquidacion", "Recaudacion"],
                    "Desistido" => ["Ingreso", "Denuncio", "Programacion", "Visita", "Presupuesto", "Liquidacion", "Recaudacion"],
                    "DesistidoSinVisita" => ["Ingreso", "Denuncio", "Programacion", "Presupuesto", "Liquidacion", "Recaudacion"]
                ],
                "sub_states" => [
                    "Ingreso" => [
                        "column" => "signature_status",
                        "values" => [
                            "generado",
                            "enviado a acepta",
                            "notificado",
                            "contrato pendiente",
                            "mandato pendiente",
                            "firmados"
                        ],
                        "default" => "generado",
                        "final" => "firmados"
                    ],
                    "Denuncio" => [
                        "column" => "denounce_status",
                        "values" => ["pendiente", "en proceso", "realizado"],
                        "default" => "pendiente",
                        "final" => "realizado"
                    ],
                    "Programacion" => [
                        "column" => "scheduling_status",
                        "values" => ["pendiente", "en proceso", "realizado"],
                        "default" => "pendiente",
                        "final" => "realizado"
                    ],
                    "Visita" => [
                        "column" => "visit_status",
                        "values" => ["pendiente", "en proceso", "realizado"],
                        "default" => "pendiente",
                        "final" => "realizado"
                    ],
                    "Presupuesto" => [
                        "column" => "budget_status",
                        "values" => ["pendiente", "en proceso", "realizado"],
                        "default" => "pendiente",
                        "final" => "realizado"
                    ],
                    "Liquidacion" => [
                        "column" => "decision_status",
                        "values" => [
                            "en espera",
                            "aprobado",
                            "bajo deducible",
                            "rechazado aseguradora",
                            "rechazado liquidadora",
                            "impugnado"
                        ],
                        "default" => "en espera",
                        "final" => "aprobado"
                    ],
                    "Recaudacion" => [
                        "column" => "payment_status",
                        "values" => [
                            "pendiente",
                            "cobranza",
                            "parcialmente pagado",
                            "pagado",
                            "cobranza online"
                        ],
                        "default" => "pendiente",
                        "final" => "pagado"
                    ]
                ],
                "closing_steps" => [
                    "Cancelado",
                    "Desistido",
                    "DesistidoSinVisita"
                ],
                "auto_transitions" => [
                    "Ingreso" => "Denuncio",
                    "Denuncio" => "Programacion",
                    "Programacion" => "Visita",
                    "Visita" => "Presupuesto",
                    "Presupuesto" => "Liquidacion",
                    "Liquidacion" => "Recaudacion"
                ],
                "overall_status" => [
                    "column" => "overall_status",
                    "values" => ["en proceso", "con pendientes", "cerrado"],
                    "default" => "en proceso",
                    "triggers" => [
                        "closed" => [
                            "states" => ["Cancelado", "Desistido", "DesistidoSinVisita"],
                            "sub_states" => [
                                "bajo deducible",
                                "rechazado aseguradora",
                                "rechazado liquidadora",
                                "pagado"
                            ]
                        ],
                        "pending" => [
                            "sub_states" => ["pendiente"]
                        ]
                    ]
                ],
                "steps_with_whatsapp_notifications" => [
                    "Ingreso"
                ]
            ], JSON_UNESCAPED_UNICODE)]
        );
    }
}
