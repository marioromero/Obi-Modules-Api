<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mindicador' => [
        'base_uri' => env('MINDICADOR_BASE_URI', 'https://mindicador.cl/api'),
        'timeout' => (float) env('MINDICADOR_TIMEOUT', 5),
    ],

    'bcentral' => [
        'base_uri' => env('BCENTRAL_BASE_URI', 'https://si3.bcentral.cl/SieteRestWS/SieteRestWS.ashx'),
        'token' => env('BCENTRAL_TOKEN'),
        'timeout' => (float) env('BCENTRAL_TIMEOUT', 10),
        'uf_series' => env('BCENTRAL_UF_SERIES', 'F073.UFF.PRE.Z.D'),
    ],

    'uf' => [
        // Días hacia atrás en que se acepta la última UF publicada para
        // fines de semana/feriados. Evita repetir un valor viejo por semanas.
        'lookback_days' => (int) env('UF_LOOKBACK_DAYS', 7),

        // Fechas Y-m-d que deben tratarse como feriado sin publicación.
        // Formato de UF_HOLIDAYS: 2026-01-01,2026-05-01,...
        'holidays' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('UF_HOLIDAYS', ''))
        ))),

        // Cache por fecha. Segundos. 0 desactiva el cache correspondiente.
        'cache_ttl_history' => (int) env('UF_CACHE_TTL_HISTORY', 86400),
        'cache_ttl_recent' => (int) env('UF_CACHE_TTL_RECENT', 900),
        'cache_ttl_last_published' => (int) env('UF_CACHE_TTL_LAST_PUBLISHED', 300),
        'cache_ttl_error' => (int) env('UF_CACHE_TTL_ERROR', 30),

        // Archivo histórico diario de UF (storage/app/private/...).
        'history_enabled' => (bool) env('UF_HISTORY_ENABLED', true),
        'history_path' => env('UF_HISTORY_PATH', 'uf/uf-history.json'),
    ],

    'trusted_internal_api_key' => env('TRUSTED_INTERNAL_API_KEY'),

];
