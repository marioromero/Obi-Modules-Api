<?php

return [

    'paths' => ['obi/api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://127.0.0.1:8000',        // tu front local
        'http://localhost:8000',
        'https://trarocrm.cl',
        'https://nodoxteam.cl',
        'https://obi.trarocrm.cl',
],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
