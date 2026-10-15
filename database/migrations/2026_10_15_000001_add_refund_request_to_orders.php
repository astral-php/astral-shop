<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Demande de remboursement client (9.1d) — statuts + motifs.
 * Colonnes RIB déjà présentes (2026_10_14).
 */
class AddRefundRequestToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_request_status TEXT NOT NULL DEFAULT \'none\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_request_reason TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_request_at TEXT NULL');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_reject_reason TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_rejected_at TEXT NULL');
            $pdo->exec('ALTER TABLE orders ADD COLUMN refund_accepted_at TEXT NULL');
        } else {
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_request_status` VARCHAR(32) NOT NULL DEFAULT \'none\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_request_reason` TEXT NOT NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_request_at` DATETIME NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_reject_reason` TEXT NOT NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_rejected_at` DATETIME NULL');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `refund_accepted_at` DATETIME NULL');
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_request_status`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_request_reason`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_request_at`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_reject_reason`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_rejected_at`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `refund_accepted_at`');
        }
    }
}
