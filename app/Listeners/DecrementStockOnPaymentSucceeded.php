<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderBecamePaid;
use App\Services\OrderStockService;
use AstralPayment\Events\PaymentSucceeded;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Throwable;

/**
 * Décrémente le stock une seule fois lorsque le paiement est confirmé (CDC).
 * Stripe (PaymentSucceeded) ou validation admin offline (OrderBecamePaid).
 * No-op si déjà réservé à l’engagement offline (stock_applied = 1).
 */
final class DecrementStockOnPaymentSucceeded implements ListenerInterface
{
    public function __construct(
        private OrderStockService $stock,
        private Logger $logger,
    ) {}

    public function handle(EventInterface $event): void
    {
        $orderId = $this->resolveOrderId($event);
        if ($orderId < 1) {
            return;
        }

        try {
            $this->stock->applyReservation($orderId, true);
        } catch (Throwable $e) {
            $this->logger->error('Échec décrément stock après paiement', [
                'order_id' => $orderId,
                'message'  => $e->getMessage(),
            ]);
        }
    }

    private function resolveOrderId(EventInterface $event): int
    {
        if ($event instanceof PaymentSucceeded) {
            return $event->orderId;
        }
        if ($event instanceof OrderBecamePaid) {
            return $event->orderId;
        }

        return 0;
    }
}
