<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Core\Console\CommandInterface;
use Core\Console\Console;

/**
 * Audit rapide avant mise en production (secrets / valeurs démo / runtime).
 *
 *   php bin/console shop:check-production
 */
final class CheckProductionCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'shop:check-production';
    }

    public function getDescription(): string
    {
        return 'Vérifie APP_ENV, debug, secrets et valeurs démo avant ouverture';
    }

    public function execute(array $args, Console $console): int
    {
        $warnings = [];
        $blockers = [];

        $env = strtolower((string) ($_ENV['APP_ENV'] ?? 'production'));
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $baseUrl = (string) ($_ENV['APP_URL'] ?? $_ENV['APP_BASE_URL'] ?? '');

        if ($env !== 'production') {
            $warnings[] = "APP_ENV={$env} (attendu production pour une boutique ouverte).";
        }
        if ($debug) {
            $blockers[] = 'APP_DEBUG=true — désactiver en production.';
        }
        if ($baseUrl === '' || str_contains($baseUrl, '.local') || str_starts_with($baseUrl, 'http://')) {
            $warnings[] = 'APP_URL / APP_BASE_URL semble local ou non HTTPS (' . ($baseUrl !== '' ? $baseUrl : 'vide') . ').';
        }

        $siret = (string) ($_ENV['SHOP_LEGAL_SIRET'] ?? '');
        if ($siret === '' || str_contains($siret, '000 000 000')) {
            $warnings[] = 'SHOP_LEGAL_SIRET encore démo / vide.';
        }

        $host = (string) ($_ENV['SHOP_LEGAL_HOST'] ?? '');
        if ($host === '' || str_contains($host, '[Hébergeur')) {
            $warnings[] = 'SHOP_LEGAL_HOST encore placeholder démo.';
        }

        $iban = (string) ($_ENV['SHOP_TRANSFER_IBAN'] ?? '');
        if (str_contains($iban, 'FR76 3000 6000 0112 3456 7890 189')) {
            $warnings[] = 'SHOP_TRANSFER_IBAN = IBAN de démonstration.';
        }

        $mailHost = (string) ($_ENV['MAIL_HOST'] ?? '');
        $mailPort = (int) ($_ENV['MAIL_PORT'] ?? 0);
        if ($mailHost === '127.0.0.1' && $mailPort === 1025) {
            $warnings[] = 'MAIL_* pointe encore vers Mailpit local (127.0.0.1:1025).';
        }

        $stripeSecret = (string) ($_ENV['STRIPE_SECRET_KEY'] ?? '');
        if ($stripeSecret !== '' && str_starts_with($stripeSecret, 'sk_test_')) {
            $warnings[] = 'STRIPE_SECRET_KEY est une clé test (sk_test_) — live requis en prod carte.';
        }
        if ($stripeSecret === '' && $this->paymentMethodsInclude('card')) {
            $warnings[] = 'Carte activée mais STRIPE_SECRET_KEY vide.';
        }

        $paypalMode = strtolower((string) ($_ENV['PAYPAL_MODE'] ?? 'sandbox'));
        if ($paypalMode === 'sandbox' && $this->paymentMethodsInclude('paypal')) {
            $warnings[] = 'PAYPAL_MODE=sandbox alors que PayPal est dans les moyens de paiement.';
        }

        $cacheDriver = strtolower((string) ($_ENV['CACHE_DRIVER'] ?? 'file'));
        if ($cacheDriver === 'redis' && !extension_loaded('redis')) {
            $blockers[] = 'CACHE_DRIVER=redis mais extension PHP redis absente.';
        }

        $queueDriver = strtolower((string) ($_ENV['QUEUE_DRIVER'] ?? 'database'));
        if ($queueDriver === 'redis' && !extension_loaded('redis')) {
            $blockers[] = 'QUEUE_DRIVER=redis mais extension PHP redis absente.';
        }
        if ($queueDriver === 'database') {
            $warnings[] = 'QUEUE_DRIVER=database — prévoir un cron `worker:work --stop-when-empty` (sinon mails/PDF en attente).';
        }

        if (!function_exists('opcache_get_status')) {
            $warnings[] = 'OPcache non disponible (extension absente ou CLI). Vérifier sur le PHP web en prod.';
        } elseif (PHP_SAPI !== 'cli') {
            $status = opcache_get_status(false);
            if (is_array($status) && empty($status['opcache_enabled'])) {
                $warnings[] = 'OPcache chargé mais désactivé.';
            }
        } else {
            $console->info('OPcache : vérifier aussi via phpinfo() côté web (le CLI peut différer).');
        }

        foreach ($warnings as $w) {
            $console->warning($w);
        }
        foreach ($blockers as $b) {
            $console->error($b);
        }

        if ($blockers === [] && $warnings === []) {
            $console->success('Aucun problème détecté par shop:check-production.');
            return 0;
        }

        if ($blockers !== []) {
            $console->error(count($blockers) . ' blocker(s), ' . count($warnings) . ' warning(s). Voir docs/Installation/production-checklist.md');
            return 1;
        }

        $console->info(count($warnings) . ' warning(s) — corriger avant ouverture publique.');
        return 0;
    }

    private function paymentMethodsInclude(string $method): bool
    {
        $raw = $_ENV['SHOP_PAYMENT_METHODS'] ?? '';
        if ($raw === '') {
            // défaut shop : card + offline (+ paypal si configuré côté config/shop.php)
            return in_array($method, ['card', 'transfer', 'check'], true);
        }

        $parts = array_map('trim', explode(',', (string) $raw));

        return in_array($method, $parts, true);
    }
}
