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
            "SELECT o.*, s.method_code AS shipping_method_code
             FROM {$this->getTable()} o
             LEFT JOIN order_shipments s ON s.order_id = o.id
             WHERE o.user_id = :uid
               AND o.status != 'cancelled'
             ORDER BY o.created_at DESC, o.id DESC",
            [':uid' => $userId],
        );
    }

    public function findForUser(int $orderId, int $userId): ?Order
    {
        $rows = $this->query(
            "SELECT o.*, s.method_code AS shipping_method_code
             FROM {$this->getTable()} o
             LEFT JOIN order_shipments s ON s.order_id = o.id
             WHERE o.id = :id AND o.user_id = :uid
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

    public function findByPaypalOrderId(string $paypalOrderId): ?Order
    {
        if ($paypalOrderId === '') {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE paypal_order_id = :pid
             LIMIT 1",
            [':pid' => $paypalOrderId],
        );

        /** @var Order|null */
        return $rows[0] ?? null;
    }

    public function findByPaypalCaptureId(string $captureId): ?Order
    {
        if ($captureId === '') {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE paypal_capture_id = :cid
             LIMIT 1",
            [':cid' => $captureId],
        );

        /** @var Order|null */
        return $rows[0] ?? null;
    }

    /**
     * Toutes les commandes (admin), client joint.
     *
     * @param ?string $paymentStatus       pending|paid|failed|refunded (colonne status)
     * @param ?string $fulfillmentStatus   awaiting|preparing|shipped
     * @param ?string $refundRequestStatus none|requested|accepted|rejected
     * @return list<Order>
     */
    public function findAllWithUser(
        ?string $paymentStatus = null,
        ?string $fulfillmentStatus = null,
        ?string $refundRequestStatus = null,
    ): array {
        $sql = "SELECT o.*, u.name AS user_name, u.email AS user_email,
                       s.method_code AS shipping_method_code
                FROM {$this->getTable()} o
                LEFT JOIN users u ON u.id = o.user_id
                LEFT JOIN order_shipments s ON s.order_id = o.id";
        $params = [];
        $where  = [];

        if ($paymentStatus !== null && $paymentStatus !== '') {
            $where[] = 'o.status = :payment_status';
            $params[':payment_status'] = $paymentStatus;
        } else {
            // Liste admin par défaut : masquer les annulées (changement de moyen, abandons).
            $where[] = "o.status != 'cancelled'";
        }

        if ($fulfillmentStatus !== null && $fulfillmentStatus !== '') {
            $where[] = 'o.fulfillment_status = :fulfillment_status';
            $params[':fulfillment_status'] = $fulfillmentStatus;
        }

        if ($refundRequestStatus !== null && $refundRequestStatus !== '') {
            $where[] = 'o.refund_request_status = :refund_request_status';
            $params[':refund_request_status'] = $refundRequestStatus;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

        /** @var list<Order> */
        return $this->query($sql, $params);
    }

    /** Nombre de demandes client en attente (requested). */
    public function countRefundRequestsPending(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->getTable()}
             WHERE refund_request_status = :status
               AND status != 'cancelled'",
        );
        $stmt->execute([':status' => Order::REFUND_REQUEST_REQUESTED]);

        return (int) $stmt->fetchColumn();
    }

    public function findByIdWithUser(int $orderId): ?Order
    {
        $rows = $this->query(
            "SELECT o.*, u.name AS user_name, u.email AS user_email,
                    s.method_code AS shipping_method_code
             FROM {$this->getTable()} o
             LEFT JOIN users u ON u.id = o.user_id
             LEFT JOIN order_shipments s ON s.order_id = o.id
             WHERE o.id = :id
             LIMIT 1",
            [':id' => $orderId],
        );

        /** @var Order|null */
        return $rows[0] ?? null;
    }

    /**
     * Pending offline dont l’échéance de paiement est dépassée.
     *
     * @return list<Order>
     */
    public function findExpiredOfflinePending(string $now): array
    {
        /** @var list<Order> */
        return $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE status = 'pending'
               AND payment_method IN ('transfer', 'check')
               AND payment_due_at IS NOT NULL
               AND payment_due_at < :now
             ORDER BY payment_due_at ASC, id ASC",
            [':now' => $now],
        );
    }

    /**
     * Compteurs par statut paiement, optionnellement bornés par le filtre colis.
     * Clé '' = Actives (hors cancelled).
     *
     * @return array<string, int>
     */
    public function countGroupedByPayment(?string $fulfillmentStatus = null): array
    {
        $sql = "SELECT status, COUNT(*) AS c FROM {$this->getTable()}";
        $params = [];
        if ($fulfillmentStatus !== null && $fulfillmentStatus !== '') {
            $sql .= ' WHERE fulfillment_status = :fulfillment';
            $params[':fulfillment'] = $fulfillmentStatus;
        }
        $sql .= ' GROUP BY status';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $byStatus = [];
        $actives = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $status = (string) $row['status'];
            $count = (int) $row['c'];
            $byStatus[$status] = $count;
            if ($status !== 'cancelled') {
                $actives += $count;
            }
        }

        return [
            ''          => $actives,
            'pending'   => $byStatus['pending'] ?? 0,
            'paid'      => $byStatus['paid'] ?? 0,
            'failed'    => $byStatus['failed'] ?? 0,
            'refunded'  => $byStatus['refunded'] ?? 0,
            'cancelled' => $byStatus['cancelled'] ?? 0,
        ];
    }

    /**
     * Compteurs par statut colis, optionnellement bornés par le filtre paiement.
     * Sans filtre paiement : même périmètre que « Actives » (hors cancelled).
     * Clé '' = Tous (somme du périmètre).
     *
     * @return array<string, int>
     */
    public function countGroupedByFulfillment(?string $paymentStatus = null): array
    {
        $sql = "SELECT fulfillment_status, COUNT(*) AS c FROM {$this->getTable()}";
        $params = [];
        if ($paymentStatus !== null && $paymentStatus !== '') {
            $sql .= ' WHERE status = :payment';
            $params[':payment'] = $paymentStatus;
        } else {
            $sql .= " WHERE status != 'cancelled'";
        }
        $sql .= ' GROUP BY fulfillment_status';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $byStatus = [];
        $total = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $status = (string) $row['fulfillment_status'];
            $count = (int) $row['c'];
            $byStatus[$status] = $count;
            $total += $count;
        }

        return [
            ''          => $total,
            'awaiting'  => $byStatus['awaiting'] ?? 0,
            'preparing' => $byStatus['preparing'] ?? 0,
            'shipped'   => $byStatus['shipped'] ?? 0,
        ];
    }
}
