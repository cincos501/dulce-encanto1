<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Datos del Negocio (Dulce Encanto)
    |--------------------------------------------------------------------------
    |
    | Información de la pastelería usada por el asistente virtual "Encantito"
    | (ubicación, contacto y datos generales).
    |
    */

    'name' => env('BUSINESS_NAME', 'Dulce Encanto'),
    'city' => env('BUSINESS_CITY', 'Tarija, Bolivia'),
    'currency' => 'Bs.',

    'description' => env(
        'BUSINESS_DESCRIPTION',
        'Repostería de Tarija, especializada en tortas de chocolate, tres leches, queques y pasteles personalizados para toda ocasión. Horneamos felicidad para cada ocasión.'
    ),

    'phone' => env('BUSINESS_PHONE', '+591 70012345'),

    'location' => [
        'address' => env(
            'BUSINESS_ADDRESS',
            'Avenida Circunvalación, Barrio Carlos Wagner, Av. Los Crespones, Tarija, Bolivia'
        ),
        'latitude' => (float) env('BUSINESS_LATITUDE', -21.50631914541871),
        'longitude' => (float) env('BUSINESS_LONGITUDE', -64.75171651298785),
        // Enlace que genera la tarjeta/preview de mapa en WhatsApp.
        'maps_url' => env(
            'BUSINESS_MAPS_URL',
            'https://maps.google.com/?q=-21.50631914541871,-64.75171651298785'
        ),
        'reference' => env('BUSINESS_LOCATION_REFERENCE', 'A pocos metros de la Av. Los Crespones.'),
    ],
];
