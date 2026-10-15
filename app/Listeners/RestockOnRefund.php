<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Events\OrderBecameRefunded;
use App\Services\OrderStockService;
use AstralPayment\Events\PaymentRefunded;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Throwable;

/**
 * Restock idempotent après passage en remboursée (admin ou webhook Stripe).
 */
final class RestockOnRefund implements ListenerInterface
{
    public function __construct(
        private OrderStockService $stock,
        private OrderDao $orderDao,
        private Logger $logger,
    ) {}

    public function handle(EventInterface $event): void
    {
        $orderId = $this->resolveOrderId($event);
        if ($orderId < 1) {
            return;
        }

        try {
            $order = $this->orderDao->findById($orderId);
            if ($order !== null && ($order->refunded_at === null || trim((string) $order->refunded_at) === '')) {
                $this->orderDao->update($orderId, [
                    'refunded_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->stock->releaseReservation($orderId, true);
        } catch (Throwable $e) {
            $this->logger->error('Échec restock après remboursement', [
                'order_id' => $orderId,
                'message'  => $e->getMessage(),
            ]);
        }
    }

    private function resolveOrderId(EventInterface $event): int
    {
        if ($event instanceof OrderBecameRefunded) {
            return $event->orderId;
        }
        if ($event instanceof PaymentRefunded) {
            return $event->orderId;
        }

        return 0;
    }
}
