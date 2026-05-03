<?php

return [
    'shipping' => [
        'default_method' => 'standard',
        'methods' => [
            'standard' => [
                'name' => 'Standard Delivery',
                'amount' => 80.00,
                'active' => true,
                'countries' => ['BD'],
            ],
            'express' => [
                'name' => 'Express Delivery',
                'amount' => 150.00,
                'active' => true,
                'countries' => ['BD'],
            ],
        ],
    ],

    'payment' => [
        'default_method' => 'cod',
        'methods' => [
            'cod' => [
                'name' => 'Cash On Delivery',
                'active' => true,
            ],
            'manual_bank' => [
                'name' => 'Manual Bank Transfer',
                'active' => true,
            ],
        ],
    ],
];
