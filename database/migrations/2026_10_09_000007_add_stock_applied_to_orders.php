<?php

declare(strict_types=1);

use Database\Migration\Migration;

class AddStockAppliedToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE orders ADD COLUMN stock_applied INTEGER NOT NULL DEFAULT 0');
        } else {
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `stock_applied` TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    public function down(PDO $pdo): void
    {
        // SQLite ne supporte pas DROP COLUMN facilement — no-op volontaire
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `stock_applied`');
        }
    }
}
