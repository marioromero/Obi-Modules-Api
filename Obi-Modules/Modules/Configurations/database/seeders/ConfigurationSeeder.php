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

        DB::table('configurations')->updateOrInsert(
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

        DB::table('configurations')->updateOrInsert(
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

        DB::table('configurations')->updateOrInsert(
            ['type_id' => 4],
            ['content' => json_encode($userResponsibilities, JSON_UNESCAPED_UNICODE)]
        );

        // 5) Columns_by_rol
        $columnsByRolTypeId = DB::table('types')->where('name', 'Columns_by_rol')->value('id');

        if ($columnsByRolTypeId) {
            $columnsByRole = [
                // 1 = Administrador
                '1' => [
                    'code','customer_name','customer_dni','state','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'assigned_user_name','consultant_name','agent_name',
                    'payment_status','amount_owed','amount_paid','advisory_amount',
                    'probable_payment_date','overall_status','sent_to_acepta',
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
                    'code','customer_name','customer_dni','state','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'assigned_user_name','agent_name','consultant_name',
                    'scheduling_status','visit_status','inspection_date','property_type',
                    'budget_status','budget_sending_date','settlement_report_date',
                    'overall_status'
                ],

                // 4 = Administrativo
                '4' => [
                    'code','customer_name','customer_dni','state','accident_type_name',
                    'created_at','commune_name','property_address','bank_name',
                    'document_signing_date','approved_amount',
                    'sent_to_acepta','signature_status','bank_service_number',
                    'accident_number','complaint_date','collection_date',
                    'online_collection_date','payment_status','amount_owed',
                    'amount_paid','is_duplicated'
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

            DB::table('configurations')->updateOrInsert(
                ['type_id' => $columnsByRolTypeId],
                ['content' => json_encode($columnsByRole, JSON_UNESCAPED_UNICODE)]
            );
        }
    }
}
