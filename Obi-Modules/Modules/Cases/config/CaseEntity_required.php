<?php
return [
    'Ingreso' => [
        'customer_id','agreement_id','bank_id','commune_id',
        'accident_type_id','agent_id','property_address',
        'property_type','is_duplicated',
    ],
    'Denuncio' => [
        'complaint_date','date_of_loss','bank_service_number',
    ],
    'Visita' => [
        'inspection_date','accident_number','insurer_id',
        'loss_adjuster_id','consultant_id',
    ],
    'Presupuesto' => [
        'budget_sending_date','contestation_date',
    ],
    'Liquidacion' => [
        'settlement_report_date','approved_amount',
        'uf_approved','advisory_amount',
    ],
    'Recaudacion' => [
        'probable_payment_date','collection_date','payment_status',
        'amount_paid','amount_owed','online_collection_date',
    ],
];
