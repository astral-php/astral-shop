<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\Order;
use Database\AbstractDao;

/**
 * @extends AbstractDao<Order>
 */
final class OrderDao extends AbstractDao
{
    protected function getTable(): string
    {
        return 'orders';
    }

    protected function getModelClass(): string
    {
        return Order::class;
    }

    /** @return list<Order> */
    public function findByUser(int $userId): array
    {
        /** @var list<Order> */
        return $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE user_id = :uid
               AND status != 'cancelled'
             ORDER BY created_at DESC, id DESC",
            [':uid' => $userId],
        );
    }

    public function findForUser(int $orderId, int $userId): ?Order
    {
        $rows = $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE id = :id AND user_id = :uid
             LIMIT 1",
            [':id' => $orderId, ':uid' => $userId],
        );

        /** @var Order|null */
        return $rows[0] ?? null;
    }

    public function findByStripeIntentId(string $intentId): ?Order
    {
        if ($intentId === '') {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE stripe_intent_id = :intent
             LIMIT 1",
            [':intent' => $intentId],
        );

        /** @var Order|null */
        return $rows[0] ?? null;
    }

    /**
     * Toutes les commandes (admin), client joint.
     *
     * @param ?string $paymentStatus     pending|paid|failed|refunded (colonne status)
     * @param ?string $fulfillmentStatus awaiting|preparing|shipped
     * @return list<Order>
     */
    public function findAllWithUser(?string $paymentStatus = null, ?string $fulfillmentStatus = null): array
    {
        $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email
                FROM {$this->getTable()} o
                LEFT JOIN users u ON u.id = o.user_id";
        $params = [];
        $where  = [];

        if ($paymentStatus !== null && $paymentStatus !== '') {
            $where[] = 'o.status = :payment_status';
            $params[':payment_status'] = $paymentStatus;
        }

        if ($fulfillmentStatus !== null && $fulfillmentStatus !== '') {
            $where[] = 'o.fulfillment_status = :fulfillment_status';
            $params[':fulfillment_status'] = $fulfillmentStatus;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

        /** @var list<Order> */
        return $this->query($sql, $params);
    }

    public function findByIdWithUser(int $orderId): ?Order
    {
        $rows = $this->query(
            "SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM {$this->getTable()} o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.id = :id
             LIMIT 1",
            [':id' => $orderId],
        );

        /** @var Order|null */
        return $rows[0] ?? null;
    }
}
