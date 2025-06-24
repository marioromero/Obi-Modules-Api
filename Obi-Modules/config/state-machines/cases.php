<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conexión y metadatos del módulo
    |--------------------------------------------------------------------------
    */
    'connection' => 'cases_db',
    'module' => 'Cases',
    'model' => 'CaseEntity',
    'table' => 'cases',
    'namespace' => 'Traro',

    /*
    |--------------------------------------------------------------------------
    | Estados globales y transiciones
    |--------------------------------------------------------------------------
    */
    'default' => 'Ingreso',
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
        'Ingreso' => ['Denuncio', 'Cancelado', 'Desistido', 'DesistidoSinVisita'],
        'Denuncio' => ['Programacion', 'Ingreso', 'Cancelado', 'Desistido', 'DesistidoSinVisita'],
        'Programacion' => ['Visita', 'Denuncio', 'Cancelado', 'Desistido', 'DesistidoSinVisita'],
        'Visita' => ['Presupuesto', 'Programacion', 'Cancelado', 'Desistido'],
        'Presupuesto' => ['Liquidacion', 'Visita', 'Cancelado', 'Desistido'],
        'Liquidacion' => ['Recaudacion', 'Presupuesto', 'Cancelado', 'Desistido'],
        'Recaudacion' => ['Cancelado', 'Desistido'],
        // Los pasos de cierre (sin columna enum propia) se listan también aquí para permitir la transición global
        'Cancelado' => [],
        'Desistido' => [],
        'DesistidoSinVisita' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sub-estados por cada estado global (sub_states)
    |--------------------------------------------------------------------------
    */
    'sub_states' => [
        'Ingreso' => [
            'column' => 'signature_status',
            'values' => [
                'generado',
                'enviado a acepta',
                'notificado',
                'contrato pendiente',
                'mandato pendiente',
                'firmados',
            ],
            'default' => 'generado',
            'final' => 'firmados',
        ],
        'Denuncio' => [
            'column' => 'denounce_status',
            'values' => ['pendiente', 'en proceso', 'realizado'],
            'default' => 'pendiente',
            'final' => 'realizado',
        ],
        'Programacion' => [
            'column' => 'scheduling_status',
            'values' => ['pendiente', 'en proceso', 'realizado'],
            'default' => 'pendiente',
            'final' => 'realizado',
        ],
        'Visita' => [
            'column' => 'visit_status',
            'values' => ['pendiente', 'en proceso', 'realizado'],
            'default' => 'pendiente',
            'final' => 'realizado',
        ],
        'Presupuesto' => [
            'column' => 'budget_status',
            'values' => ['pendiente', 'en proceso', 'realizado'],
            'default' => 'pendiente',
            'final' => 'realizado',
        ],
        'Liquidacion' => [
            'column' => 'decision_status',
            'values' => [
                'en espera',
                'aprobado',
                'bajo deducible',
                'rechazado aseguradora',
                'rechazado liquidadora',
                'impugnado',
            ],
            'default' => 'en espera',
            'final' => 'impugnado',
        ],
        'Recaudacion' => [
            'column' => 'payment_status',
            'values' => ['pendiente', 'cobranza', 'parcialmente pagado', 'pagado', 'cobranza online'],
            'default' => 'pendiente',
            'final' => 'pagado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pasos de cierre sin columna enum propia (closing_steps)
    |--------------------------------------------------------------------------
    */
    'closing_steps' => [
        'Cancelado',
        'Desistido',
        'DesistidoSinVisita',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transiciones automáticas entre estados globales (auto_transitions)
    |--------------------------------------------------------------------------
    */
    'auto_transitions' => [
        'Ingreso' => 'Denuncio',
        'Denuncio' => 'Programacion',
        'Programacion' => 'Visita',
        'Visita' => 'Presupuesto',
        'Presupuesto' => 'Liquidacion',
        'Liquidacion' => 'Recaudacion',
        // En Recaudacion no hay next global
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de overall_status y sus triggers
    |--------------------------------------------------------------------------
    */
    'overall_status' => [
        'column' => 'overall_status',
        'values' => ['en proceso', 'con pendientes', 'cerrado'],
        'default' => 'en proceso',
        'triggers' => [
            'closed' => [
                'states' => ['Cancelado', 'Desistido', 'DesistidoSinVisita'],
                'sub_states' => ['bajo deducible', 'rechazado aseguradora', 'rechazado liquidadora', 'pagado'],
            ],
            'pending' => [
                'sub_states' => ['pendiente'],
            ],
        ],
    ],
];
