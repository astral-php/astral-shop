<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Models\Order;
use Core\Logger;
use PDO;
use Throwable;

/**
 * Réservation / libération de stock liée à une commande (idempotent via stock_applied).
 */
final class OrderStockService
{
    public function __construct(
        private PDO $pdo,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private ProductDao $productDao,
        private Logger $logger,
    ) {}

    /**
     * Décrémente le stock une seule fois (stock_applied 0 → 1).
     *
     * @return bool true si un décrément a été effectué
     */
    public function applyReservation(int $orderId, bool $manageTransaction = true): bool
    {
        return $this->run($manageTransaction, function () use ($orderId): bool {
            $order = $this->orderDao->findById($orderId);
            /** @var Order|null $order */
            if ($order === null || $order->hasStockApplied()) {
                return false;
            }

            foreach ($this->orderItemDao->findByOrder($order->id) as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $product = $this->productDao->findById((int) $item->product_id);
                if ($product === null) {
                    continue;
                }

                $newStock = max(0, (int) $product->stock - (int) $item->quantity);
                $this->productDao->update($product->id, ['stock' => $newStock]);
            }

            $this->orderDao->update($order->id, ['stock_applied' => 1]);
            $this->logger->info('Stock réservé / décrémenté', ['order_id' => $order->id]);

            return true;
        });
    }

    /**
     * Réincrémente le stock une seule fois (stock_applied 1 → 0).
     *
     * @return bool true si un restock a été effectué
     */
    public function releaseReservation(int $orderId, bool $manageTransaction = true): bool
    {
        return $this->run($manageTransaction, function () use ($orderId): bool {
            $order = $this->orderDao->findById($orderId);
            /** @var Order|null $order */
            if ($order === null || !$order->hasStockApplied()) {
                return false;
            }

            foreach ($this->orderItemDao->findByOrder($order->id) as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                $product = $this->productDao->findById((int) $item->product_id);
                if ($product === null) {
                    continue;
                }

                $newStock = (int) $product->stock + (int) $item->quantity;
                $this->productDao->update($product->id, ['stock' => $newStock]);
            }

            $this->orderDao->update($order->id, ['stock_applied' => 0]);
            $this->logger->info('Stock libéré / restocké', ['order_id' => $order->id]);

            return true;
        });
    }

    /**
     * Annule une commande ouverte et libère le stock réservé.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function cancelAndRelease(Order $order, bool $manageTransaction = true): array
    {
        if ($order->isPaid()) {
            return ['ok' => false, 'error' => 'Une commande payée ne peut pas être annulée ainsi.'];
        }

        if ($order->isCancelled()) {
            return ['ok' => true];
        }

        try {
            $this->run($manageTransaction, function () use ($order): bool {
                $this->releaseReservation($order->id, false);
                $this->orderDao->update($order->id, [
                    'status'           => Order::STATUS_CANCELLED,
                    'stripe_intent_id' => null,
                    'payment_due_at'   => null,
                ]);

                return true;
            });
        } catch (Throwable $e) {
            $this->logger->error('Échec annulation + restock', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Impossible d’annuler la commande.'];
        }

        return ['ok' => true];
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function run(bool $manageTransaction, callable $fn): mixed
    {
        if (!$manageTransaction) {
            return $fn();
        }

        try {
            $this->pdo->beginTransaction();
            $result = $fn();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
