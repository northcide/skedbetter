<?php

return [
    'starter' => [
        'name' => 'Starter',
        'monthly_price' => 29,
        'annual_price' => 290,
        'monthly_price_id' => env('STRIPE_STARTER_MONTHLY_PRICE', ''),
        'annual_price_id' => env('STRIPE_STARTER_ANNUAL_PRICE', ''),
        'limits' => ['teams' => 8, 'fields' => 4, 'divisions' => 2],
    ],
    'standard' => [
        'name' => 'Standard',
        'monthly_price' => 59,
        'annual_price' => 590,
        'monthly_price_id' => env('STRIPE_STANDARD_MONTHLY_PRICE', ''),
        'annual_price_id' => env('STRIPE_STANDARD_ANNUAL_PRICE', ''),
        'limits' => ['teams' => 24, 'fields' => 12, 'divisions' => 6],
    ],
    'pro' => [
        'name' => 'Pro',
        'monthly_price' => 99,
        'annual_price' => 990,
        'monthly_price_id' => env('STRIPE_PRO_MONTHLY_PRICE', ''),
        'annual_price_id' => env('STRIPE_PRO_ANNUAL_PRICE', ''),
        'limits' => ['teams' => null, 'fields' => null, 'divisions' => null],
    ],
];
