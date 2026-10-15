<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Champs remboursement offline (RIB) + horodatage refunded_at.
 */
class AddRefundFieldsToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_iban TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_bic TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_holder TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_reference TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refunded_at TEXT NULL');
        } else {
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_iban` VARCHAR(64) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_bic` VARCHAR(16) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_holder` VARCHAR(255) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_reference` VARCHAR(64) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refunded_at` DATETIME NULL');
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_iban`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_bic`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_holder`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_reference`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refunded_at`');
        }
    }
}
