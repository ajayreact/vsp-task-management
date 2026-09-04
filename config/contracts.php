<?php

return [
    'provider' => [
        'name' => env('CONTRACT_PROVIDER_NAME', 'VSP Group Inc'),
        'authorized_person' => env('CONTRACT_PROVIDER_AUTHORIZED_PERSON', 'Ajay Oguri'),
        'phone' => env('CONTRACT_PROVIDER_PHONE', '+91-9515708888'),
        'email' => env('CONTRACT_PROVIDER_EMAIL', 'ajay@vspgi.com'),
        'website' => env('CONTRACT_PROVIDER_WEBSITE', 'https://vspgi.com'),
        'address' => env('CONTRACT_PROVIDER_ADDRESS', ''),
    ],

    'provider_signature' => env('CONTRACT_PROVIDER_SIGNATURE', 'Ajay O'),

    'number_prefix' => env('CONTRACT_NUMBER_PREFIX', 'VSP-CONTRACT'),

    'default_logo' => env('CONTRACT_DEFAULT_LOGO', 'images/branding/vsp-crm-logo.png'),

    'pdf' => [
        'background' => '#FFFDF5',
        'heading' => '#1A3A5F',
        'accent_red' => '#E31E24',
        'accent_blue' => '#1A3A5F',
        'price_green' => '#00A651',
    ],
];
