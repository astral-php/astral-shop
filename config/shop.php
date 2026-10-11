<?php

declare(strict_types=1);

/**
 * Branding boutique — adaptable multi-verticales (mode, tech, food, …).
 *
 * @return array<string, mixed>
 */
return [
    'store_name'   => $_ENV['SHOP_STORE_NAME']   ?? ($_ENV['APP_NAME'] ?? 'Astral Shop'),
    'tagline'      => $_ENV['SHOP_TAGLINE']      ?? 'Commerce moderne, expérience soignée',
    'hero_title'   => $_ENV['SHOP_HERO_TITLE']   ?? 'Une boutique claire, rapide et professionnelle',
    'hero_lead'    => $_ENV['SHOP_HERO_LEAD']    ?? 'Découvrez une sélection soignée — design épuré, parcours fluide, paiement sécurisé.',
    // Palette type Nova (indigo) — distincte du vert starter / blog
    'primary'      => $_ENV['SHOP_PRIMARY']      ?? '#4f46e5',
    'accent'       => $_ENV['SHOP_ACCENT']       ?? '#4f46e5',
    'currency'     => $_ENV['SHOP_CURRENCY']     ?? 'EUR',
    'currency_symbol' => ($_ENV['SHOP_CURRENCY_SYMBOL'] ?? '') !== ''
        ? (string) $_ENV['SHOP_CURRENCY_SYMBOL']
        : "\u{20AC}",

    // Moyens de paiement (étape 4) — instructions offline
    'payment' => [
        'methods' => ['card', 'transfer', 'check'],
        'transfer' => [
            'iban'        => (string) ($_ENV['SHOP_TRANSFER_IBAN'] ?? ''),
            'bic'         => (string) ($_ENV['SHOP_TRANSFER_BIC'] ?? ''),
            'beneficiary' => (string) ($_ENV['SHOP_TRANSFER_BENEFICIARY'] ?? ($_ENV['SHOP_STORE_NAME'] ?? 'Astral Shop')),
        ],
        'check' => [
            'payable_to' => (string) ($_ENV['SHOP_CHECK_PAYABLE_TO'] ?? ($_ENV['SHOP_STORE_NAME'] ?? 'Astral Shop')),
            'mail_to'    => (string) ($_ENV['SHOP_CHECK_MAIL_TO'] ?? ''),
        ],
    ],
];
