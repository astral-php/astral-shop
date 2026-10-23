<?php

declare(strict_types=1);

use Database\Migration\Migration;

class AddWeightGToProducts extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE products ADD COLUMN weight_g INTEGER NOT NULL DEFAULT 0');
        } else {
            $pdo->exec(
                'ALTER TABLE `products` ADD COLUMN `weight_g` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `stock`'
            );
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // SQLite < 3.35 : pas de DROP COLUMN fiable — no-op documenté
            return;
        }

        $pdo->exec('ALTER TABLE `products` DROP COLUMN `weight_g`');
    }
}
