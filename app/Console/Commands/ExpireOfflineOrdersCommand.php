<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Dao\OrderDao;
use App\Services\OrderStockService;
use Core\Console\CommandInterface;
use Core\Console\Console;
use DateTimeImmutable;

/**
 * Annule les pending chèque/virement dont payment_due_at est dépassé et restocke.
 *
 * Cron / Task Scheduler (ex. toutes les heures) :
 *   php bin/console orders:expire-offline
 */
final class ExpireOfflineOrdersCommand implements CommandInterface
{
    public function __construct(
        private OrderDao $orderDao,
        private OrderStockService $stock,
    ) {}

    public function getName(): string
    {
        return 'orders:expire-offline';
    }

    public function getDescription(): string
    {
        return 'Annule les commandes chèque/virement hors délai et libère le stock';
    }

    public function execute(array $args, Console $console): int
    {
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $expired = $this->orderDao->findExpiredOfflinePending($now);

        if ($expired === []) {
            $console->info('Aucune commande offline expirée.');
            return 0;
        }

        $ok = 0;
        $fail = 0;

        foreach ($expired as $order) {
            $result = $this->stock->cancelAndRelease($order);
            if ($result['ok'] === true) {
                $ok++;
                $console->writeln("  Annulée #{$order->id} (échéance {$order->payment_due_at})");
            } else {
                $fail++;
                $console->error("  Échec #{$order->id} : {$result['error']}");
            }
        }

        $console->success("{$ok} commande(s) expirée(s) annulée(s).");
        if ($fail > 0) {
            $console->error("{$fail} échec(s).");
            return 1;
        }

        return 0;
    }
}
