<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
return [
    'name'     => $_ENV['APP_NAME']     ?? 'Astral Shop',
    'version'  => $_ENV['APP_VERSION']  ?? '0.1.1',
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

    // astral-shipping — MVP FR : forfait + retrait (+ franco)
    'shipping' => [
        'methods' => ['flat_rate', 'store_pickup'],
        'free_shipping_threshold_cents' => (int) ($_ENV['SHOP_FREE_SHIPPING_CENTS'] ?? 6000),
        'rates_source' => 'config',
        'http_timeout' => 10,
        'flat_rate' => [
            'price_cents' => (int) ($_ENV['SHOP_FLAT_RATE_CENTS'] ?? 590),
            'label'       => 'Livraison standard',
            'countries'   => ['FR'],
            'days'        => 4,
        ],
        'store_pickup' => [
            'label' => 'Retrait en boutique',
        ],
        'rates' => [],
    ],
];

