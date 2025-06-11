<?php

return [

    // Prefijo base para todas las rutas API
    'gateway_prefix' => env('API_GATEWAY_PREFIX', 'api'),

    // Versión por defecto si no hay variable específica
    'default_version' => 'v1',

    // Mapeo módulo → versión
    'versions' => [
        'users'      => env('API_VERSION_USERS',      'v1'),
        'geography'  => env('API_VERSION_GEOGRAPHY',  'v1'),
        'customers'  => env('API_VERSION_CUSTOMERS',  'v1'),
        'cases'      => env('API_VERSION_CASES',      'v1'),
        'banks'      => env('API_VERSION_BANKS',      'v1'),
        'reports'    => env('API_VERSION_REPORTS',    'v1'),
        'schedules'  => env('API_VERSION_SCHEDULES',  'v1'),
        'mailing'    => env('API_VERSION_MAILING',    'v1'),
        'configurations'    => env('API_VERSION_CONFIGURATIONS',    'v1'),
    ],
    
    /*
     |--------------------------------------------------------------------------
     | Versión principal de la API
     |--------------------------------------------------------------------------
     */
    'version' => env('API_VERSION', 'v1'),

    /*
     |--------------------------------------------------------------------------
     | Subdominio (opcional).  Ej.: 'api'  →  api.midominio.cl
     | Déjalo null si usarás el dominio principal.
     |--------------------------------------------------------------------------
     */
    'subdomain' => env('API_SUBDOMAIN', null),
];
