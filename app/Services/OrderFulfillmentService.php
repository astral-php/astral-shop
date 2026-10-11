<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Events\OrderBecamePaid;
use App\Events\OrderShipped;
use App\Models\Order;
use Core\Events\EventDispatcher;

/**
 * Transitions admin : paiement offline + suivi colis.
 * Règle : pas de préparation / envoi tant que non payée.
 */
final class OrderFulfillmentService
{
    public function __construct(
        private OrderDao $orderDao,
        private EventDispatcher $dispatcher,
    ) {}

    /**
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markPaid(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if ($order->isPaid()) {
            return ['ok' => false, 'error' => 'Cette commande est déjà payée.'];
        }

        if (!in_array($order->payment_method, [Order::PAYMENT_TRANSFER, Order::PAYMENT_CHECK], true)) {
            return ['ok' => false, 'error' => 'La validation manuelle ne s’applique qu’au virement ou au chèque.'];
        }

        if ($order->status === 'failed') {
            return ['ok' => false, 'error' => 'Commande en échec — non applicable.'];
        }

        $this->orderDao->update($order->id, ['status' => 'paid']);
        $this->dispatcher->dispatch(new OrderBecamePaid($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markPreparing(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'La commande doit être payée avant la préparation.'];
        }

        if ($order->fulfillment_status !== Order::FULFILLMENT_AWAITING) {
            return ['ok' => false, 'error' => 'Passage en préparation impossible depuis l’état actuel.'];
        }

        $this->orderDao->update($order->id, [
            'fulfillment_status' => Order::FULFILLMENT_PREPARING,
        ]);

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markShipped(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'La commande doit être payée avant l’expédition.'];
        }

        if ($order->fulfillment_status !== Order::FULFILLMENT_PREPARING) {
            return ['ok' => false, 'error' => 'Passez d’abord la commande « En préparation ».'];
        }

        $this->orderDao->update($order->id, [
            'fulfillment_status' => Order::FULFILLMENT_SHIPPED,
        ]);
        $this->dispatcher->dispatch(new OrderShipped($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    private function requireOrder(int $orderId): ?Order
    {
        if ($orderId < 1) {
            return null;
        }

        /** @var Order|null */
        return $this->orderDao->findById($orderId);
    }
}
