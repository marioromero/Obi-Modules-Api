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

    'trusted_internal_api_key' => env('TRUSTED_INTERNAL_API_KEY'),

];
