<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Étape 1 — sépare paiement / logistique.
 *
 * - payment_method : card | transfer | check
 * - fulfillment_status : awaiting | preparing | shipped
 * - orders.status reste le statut PAIEMENT (contrat astral-payment : pending/paid/failed/refunded)
 */
class AddPaymentFulfillmentToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method TEXT NOT NULL DEFAULT 'card'");
            $pdo->exec("ALTER TABLE orders ADD COLUMN fulfillment_status TEXT NOT NULL DEFAULT 'awaiting'");
        } else {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(20) NOT NULL DEFAULT 'card'");
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `fulfillment_status` VARCHAR(20) NOT NULL DEFAULT 'awaiting'");
            $pdo->exec('ALTER TABLE `orders` ADD KEY `idx_orders_fulfillment` (`fulfillment_status`)');
            $pdo->exec('ALTER TABLE `orders` ADD KEY `idx_orders_payment_method` (`payment_method`)');
        }

        // Existants = flux Stripe carte uniquement
        $pdo->exec("UPDATE orders SET payment_method = 'card' WHERE payment_method IS NULL OR payment_method = ''");
        $pdo->exec("UPDATE orders SET fulfillment_status = 'awaiting' WHERE fulfillment_status IS NULL OR fulfillment_status = ''");
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP INDEX `idx_orders_fulfillment`');
            $pdo->exec('ALTER TABLE `orders` DROP INDEX `idx_orders_payment_method`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `payment_method`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `fulfillment_status`');
        }
    }
}
