<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * PayPal Orders v2 — identifiants order / capture sur la commande boutique.
 */
class AddPaypalIdsToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE orders ADD COLUMN paypal_order_id TEXT NULL');
            $pdo->exec('ALTER TABLE orders ADD COLUMN paypal_capture_id TEXT NULL');
        } else {
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `paypal_order_id` VARCHAR(64) NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `paypal_capture_id` VARCHAR(64) NULL');
            $pdo->exec('ALTER TABLE `orders` ADD UNIQUE KEY `uk_orders_paypal_order` (`paypal_order_id`)');
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP INDEX `uk_orders_paypal_order`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `paypal_order_id`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `paypal_capture_id`');
        }
    }
}
