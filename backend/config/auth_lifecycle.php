<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer API Token Lifecycle
    |--------------------------------------------------------------------------
    |
    | Customer storefront authentication uses Sanctum bearer tokens. These
    | defaults keep tokens bounded, scoped, and easy to prune as the account
    | surface grows.
    |
    */
    'customer_tokens' => [
        'name' => env('CUSTOMER_TOKEN_NAME', 'storefront'),
        'abilities' => ['customer'],
        'expire_minutes' => (int) env('CUSTOMER_TOKEN_EXPIRE_MINUTES', 60 * 24 * 30),
        'max_active_tokens' => (int) env('CUSTOMER_MAX_ACTIVE_TOKENS', 5),
    ],
];
