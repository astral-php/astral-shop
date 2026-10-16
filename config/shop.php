<?php

declare(strict_types=1);

/**
 * Branding boutique — adaptable multi-verticales (mode, tech, food, …).
 *
 * @return array<string, mixed>
 */
return [
    'store_name'   => $_ENV['SHOP_STORE_NAME']   ?? ($_ENV['APP_NAME'] ?? 'Astral Shop'),
    /** Adresse physique pour retrait en boutique (MVP via .env ; admin/BD plus tard). */
    'store_address' => (string) ($_ENV['SHOP_STORE_ADDRESS'] ?? ''),
    /**
     * Destinataire des alertes boutique (demandes remboursement, …).
     * Défaut : MAIL_FROM si SHOP_NOTIFY_EMAIL vide.
     */
    'notify_email' => (string) (
        ($_ENV['SHOP_NOTIFY_EMAIL'] ?? '') !== ''
            ? $_ENV['SHOP_NOTIFY_EMAIL']
            : ($_ENV['MAIL_FROM'] ?? '')
    ),
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

    /**
     * Identité légale — à établir par le marchand avant ouverture (`.env` SHOP_LEGAL_*).
     * Les pages légales sont des modèles ; ce n’est pas une conformité automatique.
     * Voir ouvrir-une-boutique-fr.md §0 / §3.
     */
    'legal' => [
        'company_name' => (string) ($_ENV['SHOP_LEGAL_COMPANY'] ?? ($_ENV['SHOP_STORE_NAME'] ?? 'Astral Shop')),
        'address'      => (string) ($_ENV['SHOP_LEGAL_ADDRESS'] ?? ($_ENV['SHOP_STORE_ADDRESS'] ?? '')),
        'email'        => (string) ($_ENV['SHOP_LEGAL_EMAIL'] ?? ($_ENV['MAIL_FROM'] ?? '')),
        'phone'        => (string) ($_ENV['SHOP_LEGAL_PHONE'] ?? ''),
        'siret'        => (string) ($_ENV['SHOP_LEGAL_SIRET'] ?? '[SIRET à renseigner]'),
        'rcs'          => (string) ($_ENV['SHOP_LEGAL_RCS'] ?? ''),
        'vat'          => (string) ($_ENV['SHOP_LEGAL_VAT'] ?? ''),
        'director'     => (string) ($_ENV['SHOP_LEGAL_DIRECTOR'] ?? '[Directeur de la publication]'),
        'host'         => (string) ($_ENV['SHOP_LEGAL_HOST'] ?? '[Hébergeur — nom, adresse, contact]'),
        /** Délai de retour / rétractation (jours calendaires) — L2. */
        'return_days'  => max(1, (int) ($_ENV['SHOP_RETURN_DAYS'] ?? 14)),
    ],

    // Moyens de paiement (étape 4) — instructions offline
    'payment' => [
        'methods' => ['card', 'transfer', 'check'],
        /** Délai pour régler chèque / virement (jours) — réserve stock jusqu’à échéance. */
        'offline_payment_days' => max(1, (int) ($_ENV['SHOP_OFFLINE_PAYMENT_DAYS'] ?? 7)),
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
