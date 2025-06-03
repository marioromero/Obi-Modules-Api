<?php

use Modules\Cases\States\Core\Draft;
use Modules\Cases\States\Core\Closed;
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
    'default' => Draft::class,
    'transitions' => [
        Modules\Cases\States\Core\Draft::class => [Modules\Cases\States\Traro\Ingreso::class],
        Modules\Cases\States\Traro\Ingreso::class => [Modules\Cases\States\Traro\Denuncio::class, Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class, Modules\Cases\States\Traro\DesistidoSinVisita::class],
        Modules\Cases\States\Traro\Denuncio::class => [Modules\Cases\States\Traro\Programacion::class, Modules\Cases\States\Traro\Ingreso::class, Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class, Modules\Cases\States\Traro\DesistidoSinVisita::class],
        Modules\Cases\States\Traro\Programacion::class => [Modules\Cases\States\Traro\Visita::class, Modules\Cases\States\Traro\Denuncio::class, Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class, Modules\Cases\States\Traro\DesistidoSinVisita::class],
        Modules\Cases\States\Traro\Visita::class => [Modules\Cases\States\Traro\Presupuesto::class, Modules\Cases\States\Traro\Programacion::class, Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class],
        Modules\Cases\States\Traro\Presupuesto::class => [Modules\Cases\States\Traro\Liquidacion::class, Modules\Cases\States\Traro\Visita::class, Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class],
        Modules\Cases\States\Traro\Liquidacion::class => [Modules\Cases\States\Traro\Recaudacion::class, Modules\Cases\States\Traro\Presupuesto::class, Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class],
        Modules\Cases\States\Traro\Recaudacion::class => [Modules\Cases\States\Traro\Cancelado::class, Modules\Cases\States\Traro\Desistido::class],
    ],
];