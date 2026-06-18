<?php

return [
    'base_url' => env('KRATOS_PAY_BASE_URL', 'https://backendpay.kratospay.com'),

    'refresh_token' => env('KRATOS_PAY_REFRESH_TOKEN'),
    'access_token' => env('KRATOS_PAY_ACCESS_TOKEN'),
    'payment_token' => env('KRATOS_PAY_PAYMENT_TOKEN'),
    'merchant_id' => env('KRATOS_PAY_MERCHANT_ID'),

    'platform_fee_percent' => (float) env('KRATOS_PAY_PLATFORM_FEE_PERCENT', 5),
    'refund_fee_percent' => (float) env('KRATOS_PAY_REFUND_FEE_PERCENT', 3),

    'withdraw_path' => env('KRATOS_PAY_WITHDRAW_PATH', '/api/wallet/public/withdraw'),
    'access_token_cache_key' => 'kratos_pay_access_token',
    'access_token_ttl_seconds' => (int) env('KRATOS_PAY_ACCESS_TOKEN_TTL', 250000),

    /*
    | Montants autorisés par Kratos Pay (XAF).
    | Doc : min 100 XAF, max 5 000 000 XAF par transaction.
    */
    'amount_min' => (float) env('KRATOS_PAY_AMOUNT_MIN', 100),
    'amount_max' => (float) env('KRATOS_PAY_AMOUNT_MAX', 5_000_000),

    /*
    | Taux indicatifs vers XAF pour les paiements Kratos (Mobile Money).
    */
    'exchange_rates_to_xaf' => [
        'EUR' => (float) env('KRATOS_PAY_EUR_TO_XAF', 655.957),
        'USD' => (float) env('KRATOS_PAY_USD_TO_XAF', 600),
        'GBP' => (float) env('KRATOS_PAY_GBP_TO_XAF', 780),
    ],
];
