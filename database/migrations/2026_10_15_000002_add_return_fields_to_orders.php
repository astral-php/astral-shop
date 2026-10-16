<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Retour colis lié au remboursement (G2 / §10.12).
 *
 * return_status : none | pending | received | waived | expired
 * shipped_at    : ancre du délai L2 (expédition / retrait)
 */
class AddReturnFieldsToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec("ALTER TABLE orders ADD COLUMN return_status TEXT NOT NULL DEFAULT 'none'");
            $pdo->exec('ALTER TABLE orders ADD COLUMN return_due_at TEXT NULL');
            $pdo->exec('ALTER TABLE orders ADD COLUMN returned_at TEXT NULL');
            $pdo->exec('ALTER TABLE orders ADD COLUMN shipped_at TEXT NULL');
        } else {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `return_status` VARCHAR(32) NOT NULL DEFAULT 'none'");
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `return_due_at` DATETIME NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `returned_at` DATETIME NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `shipped_at` DATETIME NULL');
            $pdo->exec('ALTER TABLE `orders` ADD KEY `idx_orders_return_status` (`return_status`)');
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP INDEX `idx_orders_return_status`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `return_status`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `return_due_at`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `returned_at`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `shipped_at`');
        }
    }
}
