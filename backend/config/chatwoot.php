<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Chatwoot API Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection details for your Chatwoot instance,
    | including base URL, API Access Token, Account ID, Inbox ID, and
    | verification webhook secrets.
    |
    */

    'url' => env('CHATWOOT_URL'),

    'api_token' => env('CHATWOOT_API_TOKEN'),

    'account_id' => env('CHATWOOT_ACCOUNT_ID'),

    'inbox_id' => env('CHATWOOT_INBOX_ID'),

    'webhook_secret' => env('CHATWOOT_WEBHOOK_SECRET'),

    'meta_access_token' => env('META_ACCESS_TOKEN'),

    // ID del número de WhatsApp en Meta Cloud API (para enviar ubicación nativa opcional).
    'whatsapp_phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'send_responses' => env('CHATWOOT_SEND_RESPONSES', true),

    'admin_phone' => env('CHATWOOT_ADMIN_PHONE', '+56912345678'),

    'repostero_phone' => env('CHATWOOT_REPOSTERO_PHONE', '+56900000000'),
];
