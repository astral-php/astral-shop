<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Models\Order;
use PDO;
use Throwable;

/** Hard-delete admin des commandes annulées uniquement (items, shipments, payments). */
final class CancelledOrderPurgeService
{
    public function __construct(
        private PDO $pdo,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private OrderStockService $stock,
    ) {}

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function delete(Order $order): array
    {
        if (!$order->isCancelled()) {
            return ['ok' => false, 'error' => 'Seules les commandes annulées peuvent être supprimées.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Sécurité : restock si une ancienne ligne annulée avait encore stock_applied.
            $this->stock->releaseReservation($order->id, false);

            $this->orderItemDao->deleteByOrder($order->id);

            $ship = $this->pdo->prepare('DELETE FROM order_shipments WHERE order_id = :oid');
            $ship->execute([':oid' => $order->id]);

            $pay = $this->pdo->prepare('DELETE FROM payments WHERE order_id = :oid');
            $pay->execute([':oid' => $order->id]);

            $this->orderDao->delete($order->id);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['ok' => false, 'error' => 'Impossible de supprimer la commande.'];
        }

        return ['ok' => true];
    }
}
