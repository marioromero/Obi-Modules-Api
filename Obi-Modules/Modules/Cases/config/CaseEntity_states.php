<?php

use Modules\Cases\States\Traro\Ingreso;
use Modules\Cases\States\Traro\Denuncio;
use Modules\Cases\States\Traro\Programacion;
use Modules\Cases\States\Traro\Visita;
use Modules\Cases\States\Traro\Presupuesto;
use Modules\Cases\States\Traro\Liquidacion;
use Modules\Cases\States\Traro\Recaudacion;
use Modules\Cases\States\Traro\Cancelado;
use Modules\Cases\States\Traro\Desistido;
use Modules\Cases\States\Traro\DesistidoSinVisita;

return [
    // Lista de estados globales, en orden
    'states' => array(
        0 => 'Ingreso',
        1 => 'Denuncio',
        2 => 'Programacion',
        3 => 'Visita',
        4 => 'Presupuesto',
        5 => 'Liquidacion',
        6 => 'Recaudacion',
        7 => 'Cancelado',
        8 => 'Desistido',
        9 => 'DesistidoSinVisita',
    ),

    // Estado inicial global
    'default' => Ingreso::class,

    // Transiciones permitidas
    'transitions' => [
        Ingreso::class => [Denuncio::class, Cancelado::class, Desistido::class, DesistidoSinVisita::class],
        Denuncio::class => [Programacion::class, Ingreso::class, Cancelado::class, Desistido::class, DesistidoSinVisita::class],
        Programacion::class => [Visita::class, Denuncio::class, Cancelado::class, Desistido::class, DesistidoSinVisita::class],
        Visita::class => [Presupuesto::class, Programacion::class, Cancelado::class, Desistido::class],
        Presupuesto::class => [Liquidacion::class, Visita::class, Cancelado::class, Desistido::class],
        Liquidacion::class => [Recaudacion::class, Presupuesto::class, Cancelado::class, Desistido::class],
        Recaudacion::class => [Cancelado::class, Desistido::class],
        Cancelado::class => [],
        Desistido::class => [],
        DesistidoSinVisita::class => [],
    ],

    // Sub-estados por cada estado global
    'sub_states' => array(
        'Ingreso' =>
            array(
                'column' => 'signature_status',
                'values' =>
                    array(
                        0 => 'generado',
                        1 => 'enviado a acepta',
                        2 => 'notificado',
                        3 => 'contrato pendiente',
                        4 => 'mandato pendiente',
                        5 => 'firmados',
                    ),
                'default' => 'generado',
                'final' => 'firmados',
            ),
        'Denuncio' => //Documentos firmados
            array(
                'column' => 'denounce_status',
                'values' =>
                    array(
                        0 => 'pendiente',
                        1 => 'en proceso',
                        2 => 'realizado',
                    ),
                'default' => 'pendiente',
                'final' => 'realizado',
            ),
        'Programacion' => //Denuncio realizado
            array(
                'column' => 'scheduling_status',
                'values' =>
                    array(
                        0 => 'pendiente',
                        1 => 'en proceso',
                        2 => 'realizado',
                    ),
                'default' => 'pendiente',
                'final' => 'realizado',
            ),
        'Visita' =>
            array(
                'column' => 'visit_status',
                'values' =>
                    array(
                        0 => 'pendiente',
                        1 => 'en proceso',
                        2 => 'realizado',
                    ),
                'default' => 'pendiente',
                'final' => 'realizado',
            ),
        'Presupuesto' => //Visita Realizada
            array(
                'column' => 'budget_status',
                'values' =>
                    array(
                        0 => 'pendiente',
                        1 => 'en proceso',
                        2 => 'realizado',
                    ),
                'default' => 'pendiente',
                'final' => 'realizado',
            ),
        'Liquidacion' => //Presupuesto Enviado
            array(
                'column' => 'decision_status',
                'values' =>
                    array(
                        0 => 'en espera',
                        1 => 'aprobado', //Aprobado
                        2 => 'bajo deducible', //Bajo Deducible
                        3 => 'rechazado aseguradora', //Rechazado
                        4 => 'rechazado liquidadora', //Rechazado
                        5 => 'impugnado', //Impugnado
                    ),
                'default' => 'en espera',
                'final' => 'aprobado',
            ),
        'Recaudacion' => //Aprobado
            array(
                'column' => 'payment_status',
                'values' =>
                    array(
                        0 => 'pendiente',
                        1 => 'cobranza', //Cobranza
                        2 => 'parcialmente pagado', //Deudor
                        3 => 'pagado', //Pagado
                        4 => 'cobranza online', //Cobranza Online
                    ),
                'default' => 'pendiente',
                'final' => 'pagado',
            ),
    ),

    // Estados terminales
    'closing_steps' => array(
        0 => 'Cancelado',
        1 => 'Desistido', //Desistido
        2 => 'DesistidoSinVisita', //Desistido sin visita
    ),

    // Transición automática de estado global
    'auto_transitions' => array(
        'Ingreso' => 'Denuncio',
        'Denuncio' => 'Programacion',
        'Programacion' => 'Visita',
        'Visita' => 'Presupuesto',
        'Presupuesto' => 'Liquidacion',
        'Liquidacion' => 'Recaudacion',
    ),

    // Configuración de overall_status y sus triggers
    'overall_status' => array(
        'column' => 'overall_status',
        'values' =>
            array(
                0 => 'en proceso',
                1 => 'con pendientes',
                2 => 'cerrado',
            ),
        'default' => 'en proceso',
        'triggers' =>
            array(
                'closed' =>
                    array(
                        'states' =>
                            array(
                                0 => 'Cancelado',
                                1 => 'Desistido',
                                2 => 'DesistidoSinVisita',
                            ),
                        'sub_states' =>
                            array(
                                0 => 'bajo deducible',
                                1 => 'rechazado aseguradora',
                                2 => 'rechazado liquidadora',
                                3 => 'pagado',
                            ),
                    ),
                'pending' =>
                    array(
                        'sub_states' =>
                            array(
                                0 => 'pendiente',
                            ),
                    ),
            ),
    ),
];
