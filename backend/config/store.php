<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Store Bootstrap Defaults
    |--------------------------------------------------------------------------
    |
    | These values are infrastructure / deployment defaults that allow a new
    | environment to boot with sensible store metadata before admin-managed
    | settings exist. Later B3 settings can override these values from the
    | database without changing application code.
    |
    */
    'defaults' => [
        'name' => env('STORE_NAME', env('APP_NAME', 'Laravel')),
        'currency' => env('STORE_CURRENCY', 'BDT'),
        'timezone' => env('STORE_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
        'support_email' => env('STORE_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database-Backed Settings
    |--------------------------------------------------------------------------
    |
    | Admin-editable business settings will live in the database in the later
    | settings module. These keys define the shared conventions now so the
    | application and future modules can stay consistent.
    |
    */
    'settings' => [
        'table' => 'store_settings',
        'cache_key' => 'store.settings',
        'cache_ttl' => (int) env('STORE_SETTINGS_CACHE_TTL', 300),
    ],
];
