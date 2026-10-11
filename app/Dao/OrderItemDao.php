<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\OrderItem;
use Database\AbstractDao;

/**
 * @extends AbstractDao<OrderItem>
 */
final class OrderItemDao extends AbstractDao
{
    protected function getTable(): string
    {
        return 'order_items';
    }

    protected function getModelClass(): string
    {
        return OrderItem::class;
    }

    /** @return list<OrderItem> */
    public function findByOrder(int $orderId): array
    {
        /** @var list<OrderItem> */
        return $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE order_id = :oid
             ORDER BY id ASC",
            [':oid' => $orderId],
        );
    }
}
