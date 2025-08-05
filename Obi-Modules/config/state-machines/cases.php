<?php

/*
 * $case->state->transitionTo(Denuncio::class);
 * $case->transitionToWithComments(Denuncio::class, "El cliente envió la documentación completa");
 * $case->transitionSubstate("enviado a acepta");
 * $case->transitionSubstate("firmados", "Cliente firmó todos los documentos el día 30/06");
 */

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
        'Cancelado' => [ 'Ingreso', 'Denuncio', 'Programacion', 'Visita', 'Presupuesto', 'Liquidacion', 'Recaudacion'],
        'Desistido' => [ 'Ingreso', 'Denuncio', 'Programacion', 'Visita', 'Presupuesto', 'Liquidacion', 'Recaudacion'],
        'DesistidoSinVisita' => [ 'Ingreso', 'Denuncio', 'Programacion', 'Presupuesto', 'Liquidacion', 'Recaudacion'],
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
                'generado', //anaranjado
                'enviado a acepta', // azul
                'notificado', // azul
                'contrato pendiente', // anaranjado
                'mandato pendiente', // anaranjado
                'firmados', //verde
            ],
            'default' => 'generado',
            'final' => 'firmados',
        ],
        'Denuncio' => [
            'column' => 'denounce_status',
            'values' => ['pendiente', //anaranjado
                'en proceso', // azul
                'realizado'], // verde
            'default' => 'pendiente',
            'final' => 'realizado', // verde
        ],
        'Programacion' => [
            'column' => 'scheduling_status',
            'values' => ['pendiente', // anaranjado
                'en proceso', // azul
                'realizado'], // verde
            'default' => 'pendiente',
            'final' => 'realizado', // verde
        ],
        'Visita' => [
            'column' => 'visit_status',
            'values' => ['pendiente', // anaranjado
                'en proceso', // azul
                'realizado'], // verde
            'default' => 'pendiente',
            'final' => 'realizado', // verde
        ],
        'Presupuesto' => [
            'column' => 'budget_status',
            'values' => ['pendiente', // anaranjado
                'en proceso', //azul
                'realizado'], // verde
            'default' => 'pendiente',
            'final' => 'realizado', // verde
        ],
        'Liquidacion' => [
            'column' => 'decision_status',
            'values' => [
                'en espera',  // anaranjado
                'aprobado',  // verde
                'bajo deducible', // rojo
                'rechazado aseguradora', // rojo
                'rechazado liquidadora', // rojo
                'impugnado', // rojo
            ],
            'default' => 'en espera',
            'final' => 'aprobado',
        ],
        'Recaudacion' => [
            'column' => 'payment_status',
            'values' => ['pendiente',  // anaranjado
                'cobranza',  // azul
                'parcialmente pagado', // anaranjado
                'pagado',  // verde
                'cobranza online'], // rojo
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
