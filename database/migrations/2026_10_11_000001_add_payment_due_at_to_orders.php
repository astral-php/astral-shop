<?php

declare(strict_types=1);

use Database\Migration\Migration;

class AddPaymentDueAtToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE orders ADD COLUMN payment_due_at TEXT NULL');
        } else {
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `payment_due_at` DATETIME NULL');
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `payment_due_at`');
        }
    }
}
