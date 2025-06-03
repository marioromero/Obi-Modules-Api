<?php
return [
    'module'      => 'Cases',
    'model'       => 'CaseEntity',
    'table'       => 'cases',
    'namespace'   => 'Traro',

    'states' => [
        'Ingreso',
        'Denuncio',
        'Programacion',
        'Visita',
        'Presupuesto',
        'Liquidacion',
        'Recaudacion',
        'Cancelado',
        'Desistido',
        'DesistidoSinVisita',
    ],

    'transitions' => [
        'Draft'            => ['Ingreso'],

        'Ingreso'          => ['Denuncio','Cancelado','Desistido','DesistidoSinVisita'],
        'Denuncio'         => ['Programacion','Ingreso','Cancelado','Desistido','DesistidoSinVisita'],
        'Programacion'     => ['Visita','Denuncio','Cancelado','Desistido','DesistidoSinVisita'],
        'Visita'           => ['Presupuesto','Programacion','Cancelado','Desistido'],
        'Presupuesto'      => ['Liquidacion','Visita','Cancelado','Desistido'],
        'Liquidacion'      => ['Recaudacion','Presupuesto','Cancelado','Desistido'],
        'Recaudacion'      => ['Cancelado','Desistido'],   // ← ajuste solicitado
    ],
];
