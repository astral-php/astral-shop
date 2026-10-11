<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Events\OrderBecamePaid;
use App\Models\Order;
use AstralPayment\Events\PaymentSucceeded;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use PDO;
use Throwable;

/**
 * Décrémente le stock une seule fois lorsque le paiement est confirmé (CDC).
 * Stripe (PaymentSucceeded) ou validation admin offline (OrderBecamePaid).
 */
final class DecrementStockOnPaymentSucceeded implements ListenerInterface
{
    public function __construct(
        private PDO $pdo,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private ProductDao $productDao,
        private Logger $logger,
    ) {}

    public function handle(EventInterface $event): void
    {
        $orderId = $this->resolveOrderId($event);
        if ($orderId < 1) {
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $order = $this->orderDao->findById($orderId);
            /** @var Order|null $order */
            if ($order === null) {
                $this->pdo->rollBack();
                return;
            }

            if ((int) ($order->stock_applied ?? 0) === 1) {
                $this->pdo->rollBack();
                return;
            }

            foreach ($this->orderItemDao->findByOrder($order->id) as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $product = $this->productDao->findById($item->product_id);
                if ($product === null) {
                    continue;
                }

                $newStock = max(0, (int) $product->stock - (int) $item->quantity);
                $this->productDao->update($product->id, ['stock' => $newStock]);
            }

            $this->orderDao->update($order->id, ['stock_applied' => 1]);
            $this->pdo->commit();

            $this->logger->info('Stock décrémenté après paiement', [
                'order_id' => $order->id,
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
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
