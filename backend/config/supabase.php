<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase Storage Configurations
    |--------------------------------------------------------------------------
    |
    | These credentials are used to connect and communicate with the Supabase
    | Storage REST API. URL, Bucket, and Secret Key are read from env.
    |
    */

    'url' => env('SUPABASE_URL'),
    'bucket' => env('SUPABASE_BUCKET'),
    'secret_key' => env('SUPABASE_SECRET_KEY'),
];
