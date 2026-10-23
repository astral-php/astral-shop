<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
return [
    'name'     => $_ENV['APP_NAME']     ?? 'Astral Shop',
    'version'  => $_ENV['APP_VERSION']  ?? '1.1.0',
    'env'      => $_ENV['APP_ENV']      ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'charset'  => $_ENV['APP_CHARSET']  ?? 'UTF-8',
    'base_url' => $_ENV['APP_BASE_URL'] ?? '',

    'auth_registration' => $_ENV['AUTH_REGISTRATION'] ?? 'direct',

    'github_url'  => $_ENV['ASTRAL_GITHUB_URL']  ?? 'https://github.com/astral-php',
    'website_url' => $_ENV['ASTRAL_WEBSITE_URL'] ?? 'https://github.com/astral-php',

    'mail' => [
        'driver'      => $_ENV['MAIL_DRIVER']      ?? 'mail',
        'host'        => $_ENV['MAIL_HOST']         ?? 'localhost',
        'port'        => (int) ($_ENV['MAIL_PORT']  ?? 25),
        'username'    => $_ENV['MAIL_USERNAME']     ?? '',
        'password'    => $_ENV['MAIL_PASSWORD']     ?? '',
        'encryption'  => $_ENV['MAIL_ENCRYPTION']   ?? '',
        'from'        => $_ENV['MAIL_FROM']         ?? 'noreply@localhost',
        'from_name'   => $_ENV['MAIL_FROM_NAME']    ?? 'Astral Shop',
    ],

    // astral-shipping ^0.5 — Profil B : forfait + retrait + Colissimo/MR (barèmes DB)
    // Profil A (MVP) : methods => ['flat_rate', 'store_pickup'], rates_source => 'config'
    'shipping' => [
        'methods' => [
            'flat_rate',
            'store_pickup',
            'colissimo_home',
            'colissimo_pickup',
            'mondial_relay',
        ],
        'free_shipping_threshold_cents' => (int) ($_ENV['SHOP_FREE_SHIPPING_CENTS'] ?? 6000),
        'rates_source' => 'database',
        'http_timeout' => 10,
        'flat_rate' => [
            'price_cents' => (int) ($_ENV['SHOP_FLAT_RATE_CENTS'] ?? 590),
            'label'       => 'Livraison standard',
            'countries'   => ['FR'],
            'days'        => 4,
        ],
        'store_pickup' => [
            'label'   => 'Retrait en boutique',
            'address' => (string) ($_ENV['SHOP_STORE_ADDRESS'] ?? ''),
        ],
        'rates' => [],
    ],
];

