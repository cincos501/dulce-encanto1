<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Banco Económico (Baneco) API Market Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for integrating with Baneco Gateway.
    |
    */

    'base_url' => env('BANECO_BASE_URL', 'https://apimktdesa.baneco.com.bo/ApiGateway/'),

    'username' => env('BANECO_USERNAME'),

    'password' => env('BANECO_PASSWORD'),

    'aes_key' => env('BANECO_AES_KEY'),

    'account' => env('BANECO_ACCOUNT'),

    'timeout' => (int) env('BANECO_TIMEOUT', 30),

    'verify_ssl' => (bool) env('BANECO_VERIFY_SSL', true),

    'notification_enabled' => (bool) env('BANECO_NOTIFICATION_ENABLED', true),

    'qr_expiration_days' => (int) env('BANECO_QR_EXPIRATION_DAYS', 1),
];
