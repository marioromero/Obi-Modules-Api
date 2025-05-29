<?php
/**
 * Ejemplo:  config/state-machines/cases.php
 */
return [
    // El módulo nWidart donde vive el modelo
    'module'      => 'Cases',

    // Clase Eloquent (dentro de Modules\Cases\Models)
    'model'       => 'CaseEntity',

    // Tabla que recibirá la columna 'state'
    'table'       => 'cases',

    // Subcarpeta donde quedarán los estados concretos
    'namespace'   => 'Traro',

    // Estados de negocio (sin Draft / Closed)
    'states'      => ['Captured','Denounced','Scheduled','Inspected','Budgeted','Notified'],

    // Mapa “de → [a, b]”.  Draft y Closed siempre existen en Core
    'transitions' => [
        'Draft'     => ['Captured'],
        'Captured'  => ['Denounced'],
        'Denounced' => ['Scheduled'],
        'Scheduled' => ['Inspected'],
        'Inspected' => ['Budgeted'],
        'Budgeted'  => ['Notified'],
        'Notified'  => ['Closed'],
    ],
];
