<?php

return [
    // Reglas comunes
    '__common' => [
        // Etiquetas comunes
        'map' => [
            'id'         => 'ID',
            'created_at' => 'Creado',
        ],
        // Tokens que deben quedar en MAYÚSCULAS dentro de las etiquetas
        'exceptions' => ['ID','RUT','UF','JSON','URL','API','NRO'],
    ],

    // Aliases por si alguna vez llegan claves en “español”
    'aliases' => [
        'rut'       => 'dni',
        'telefono'  => 'phone',
        'telefono1' => 'phone',
        'telefono2' => 'phone2',
    ],

    // CASES (tabla + enums + vista v_cases_details)
    'cases' => [
        'map' => [
            // tabla cases
            'id'                     => 'ID',
            'state'                  => 'Estado',
            'code'                   => 'Código',
            'sent_to_acepta'         => 'Enviado a Acepta',
            'priority_id'            => 'Prioridad (ID)',
            'created_at'             => 'Creado',
            'agreement_id'           => 'Convenio (ID)',
            'property_address'       => 'Dirección de la propiedad',
            'inspection_date'        => 'F visita',
            'document_signing_date'  => 'Fecha firma documentos',
            'complaint_date'         => 'Fecha de denuncia',
            'collection_date'        => 'Fecha recaudación',
            'budget_sending_date'    => 'Fecha envío presupuesto',
            'settlement_report_date' => 'Fecha informe de liquidación',
            'probable_payment_date'  => 'Fecha de pago',
            'online_collection_date' => 'Fecha recaudación online',
            'accident_number'        => 'NRO siniestro',
            'bank_service_number'    => 'NRO atencion banco',
            'advisory_amount'        => 'Monto asesoría',
            'accident_type_id'       => 'Tipo de siniestro (ID)',
            'phone'                  => 'Teléfono',
            'user_name'              => 'Ejecutivo asignado',
            'is_duplicated'          => 'Duplicado',
            'description'            => 'Descripción',
            'comments'               => 'Comentarios',
            'resolution'             => 'Resolución',
            'comments_programming' => 'Comentarios de programación',

            'date_of_loss'           => 'Fecha del siniestro',
            'property_type'          => 'Tipo de propiedad',
            'contestation_date'      => 'Fecha de impugnación',

            'customer_id'            => 'Cliente (ID)',
            'created_by'             => 'Creado por (ID)',
            'assigned_user'          => 'Usuario asignado (ID)',
            'commune_id'             => 'Comuna (ID)',
            'consultant_id'          => 'Asesor (ID)',

            'approved_amount'        => 'Monto aprobado',
            'uf_approved'            => 'UF aprobadas',
            'amount_owed'            => 'Monto adeudado',
            'amount_owed_including_vat' => 'Deuda mas IVA',
            'amount_paid'            => 'Monto pagado',

            'bank_id'                => 'Banco (ID)',
            'insurer_id'             => 'Aseguradora (ID)',
            'loss_adjuster_id'       => 'Liquidadora (ID)',

            'schedule_message_sent'      => 'Mensaje enviado',
            'schedule_message_confirmed' => 'Mensaje confirmado',
            'schedule_inspection_failed' => 'Visita fallida',
            'schedule_inspection_time'   => 'Hr visita',
            'schedule_liquidator_inspector_info' => 'Información inspector',
            'softdeleted'            => 'Eliminación parcial',

            'case_code'              => 'TR',
            'inspection_failed'      => 'Visita fallida',

            // enums / estados
            'signature_status'       => 'Estado de firma',
            'denounce_status'        => 'Denuncio - estado',
            'scheduling_status'      => 'Programación - estado',
            'revoked'                => 'Revocado',
            'visit_status'           => 'Visita - estado',
            'budget_status'          => 'Presupuesto - estado',
            'decision_status'        => 'Decisión',
            'payment_status'         => 'Recaudación - estado',
            'overall_status'         => 'Estado global',

            // v_cases_details extras
            'fecha_documentos_listos'                    => 'Fecha documentos listos',
            'contrato_enviado_a_acepta'                 => 'Contrato enviado a Acepta',
            'fecha_firma_contrato'                      => 'Fecha firma contrato',
            'mandato_enviado_a_acepta'                  => 'Mandato enviado a Acepta',
            'fecha_firma_mandato'                       => 'Fecha firma mandato',
            'numero_notificaciones_documentos_enviados' => 'NRO notificaciones doc. enviados',
            'numero_notificaciones_documento_pendiente' => 'NRO notificaciones doc. pendiente',
            'notificacion_documento_firmado'            => 'Notificación doc. firmado',
            'active_notifications'                      => 'Notificaciones activas',

            'last_state_change_user_id'   => 'Último cambio por (ID)',
            'last_state_change_user_name' => 'Último cambio por',
            'last_state_change_at'        => 'Fecha último cambio',

            'customer_name'           => 'Nombre del cliente',
            'customer_dni'            => 'RUT del cliente',
            'customer_address'        => 'Dirección del cliente',
            'customer_commune_name'   => 'Comuna del cliente',

            'agent_id'                => 'Ejecutivo (ID)',
            'agent_name'              => 'Ejecutivo',

            'bank_name'               => 'Banco',
            'insurer_name'            => 'Aseguradora',
            'loss_adjuster_name'      => 'Liquidadora',

            'priority_name'           => 'Prioridad',
            'agreement_name'          => 'Convenio',
            'accident_type_name'      => 'Tipo de siniestro',

            'consultant_name'         => 'Asesor',
            'assigned_user_name'      => 'Usuario asignado',
            'created_by_name'         => 'Creado por',

            'commune_name'            => 'Comuna (caso)',

            'step_logs_json'          => 'Logs de pasos (JSON)',
            'case_flow_last_json'     => 'Último case flow (JSON)',
            'step_logs'               => 'Logs de pasos',
            'case_flow_last'          => 'Último case flow',
            'case_flows_last'         => 'Último case flow',
        ],
    ],

    // CUSTOMERS (tabla + vista v_customers_details)
    'customers' => [
        'map' => [
            // tabla customers
            'id'             => 'ID',
            'name'           => 'Nombre',
            'lastname'       => 'Apellido',
            'full_name'      => 'Nombre completo',
            'dni'            => 'RUT',
            'serial_number'  => 'Número de serie',
            'username'       => 'Usuario',
            'password'       => 'Password',
            'email'          => 'Correo',
            'address'        => 'Dirección',
            'phone'          => 'Teléfono 1',
            'phone2'         => 'Teléfono 2',
            'gender'         => 'Género',
            'marital_status' => 'Estado civil',
            'occupation'     => 'Profesión',
            'nationality'    => 'Nacionalidad',
            'tags'           => 'Etiquetas',
            'commune_id'     => 'Comuna (ID)',
            'assigned_agent' => 'Ejecutivo asignado (ID)',
            'comments'       => 'Comentarios',

            // v_customers_details extras
            'commune_name'   => 'Comuna',
            'user_name'      => 'Ejecutivo asignado',
        ],
    ],

        // MAILING
        'mailing' => [
            'map' => [
                // v_customers_mailing
                'customer_id'   => 'Cliente (ID)',
                'name'          => 'Nombre',
                'lastname'      => 'Apellido',
                'email'         => 'Correo',
                'tags'          => 'Etiquetas',
                'commune_name'  => 'Comuna',
                'province_name' => 'Provincia',
                'region_name'   => 'Región',
                'cases_count'   => 'N casos',

                // customers_sets / v_customers_sets_list
                'created_at'        => 'Creado',
                'recipients_count'  => 'N destinatarios',
                'last_used_at'      => 'Último uso',
                'created_by_name'   => 'Creado por',

                // email_templates
                'content'     => 'Contenido',
                'department'  => 'Departamento',
                'user'        => 'Creado por',

                // ids internos (por si alguna vez se muestran)
                'department_id'      => 'Departamento (ID)',
                'email_template_id'  => 'Plantilla (ID)',
                'customer_set_id'    => 'Set (ID)',
                'user_name' => 'Creado por',

                'schedule_id'          => 'Programación (ID)',
                'customer_set_name'    => 'Nombre del set',
                'start_in'             => 'Fecha inicio',
                'ending_at'            => 'Fecha término',
                'status_sends'         => 'Envíos',
                'is_retry'             => 'Reintento',
                'created_by_user_id'   => 'Creador (ID)',
                'created_by_user_name' => 'Creado por',
                'status'               => 'Estado',
            ],
        ],

];
