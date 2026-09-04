<?php

return [
    'provider' => [
        'name' => env('CONTRACT_PROVIDER_NAME', 'VSP Solutions'),
        'authorized_person' => env('CONTRACT_PROVIDER_AUTHORIZED_PERSON', ''),
        'phone' => env('CONTRACT_PROVIDER_PHONE', ''),
        'email' => env('CONTRACT_PROVIDER_EMAIL', env('MAIL_FROM_ADDRESS', '')),
        'website' => env('CONTRACT_PROVIDER_WEBSITE', env('APP_URL', '')),
        'address' => env('CONTRACT_PROVIDER_ADDRESS', ''),
    ],

    'number_prefix' => env('CONTRACT_NUMBER_PREFIX', 'VSP-CONTRACT'),
];
