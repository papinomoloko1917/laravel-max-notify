<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'dahua' => [
        'base_url' => env('DAHUA_BASE_URL'),
        'username' => env('DAHUA_USERNAME'),
        'password' => env('DAHUA_PASSWORD'),
        'connect_timeout' => env('DAHUA_CONNECT_TIMEOUT', 3),
        'timeout' => env('DAHUA_TIMEOUT', 10),
    ],

    'max' => [
        'base_url' => env('MAX_BASE_URL'),
        'token' => env('MAX_TOKEN'),
        'connect_timeout' => env('MAX_CONNECT_TIMEOUT', 3),
        'timeout' => env('MAX_TIMEOUT', 10),
    ],
];
