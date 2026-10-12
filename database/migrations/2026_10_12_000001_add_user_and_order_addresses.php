<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Adresses profil (livraison / facturation) + snapshot facturation sur commandes.
 */
class AddUserAndOrderAddresses extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE users ADD COLUMN shipping_name TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN shipping_line1 TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN shipping_postal TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN shipping_city TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN shipping_country TEXT NOT NULL DEFAULT \'FR\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN billing_same_as_shipping INTEGER NOT NULL DEFAULT 1');
            $pdo->exec('ALTER TABLE users ADD COLUMN billing_name TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN billing_line1 TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN billing_postal TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN billing_city TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE users ADD COLUMN billing_country TEXT NOT NULL DEFAULT \'FR\'');

            $pdo->exec('ALTER TABLE orders ADD COLUMN billing_name TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN billing_line1 TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN billing_postal TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN billing_city TEXT NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE orders ADD COLUMN billing_country TEXT NOT NULL DEFAULT \'FR\'');

            // Backfill : facturation = livraison pour les commandes existantes
            $pdo->exec(<<<'SQL'
                UPDATE orders SET
                    billing_name = shipping_name,
                    billing_line1 = shipping_line1,
                    billing_postal = shipping_postal,
                    billing_city = shipping_city,
                    billing_country = shipping_country
                WHERE billing_name = '' AND shipping_name != ''
            SQL);
        } else {
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `shipping_name` VARCHAR(150) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `shipping_line1` VARCHAR(255) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `shipping_postal` VARCHAR(20) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `shipping_city` VARCHAR(100) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `shipping_country` CHAR(2) NOT NULL DEFAULT \'FR\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `billing_same_as_shipping` TINYINT(1) NOT NULL DEFAULT 1');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `billing_name` VARCHAR(150) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `billing_line1` VARCHAR(255) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `billing_postal` VARCHAR(20) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `billing_city` VARCHAR(100) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `users` ADD COLUMN `billing_country` CHAR(2) NOT NULL DEFAULT \'FR\'');

            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `billing_name` VARCHAR(150) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `billing_line1` VARCHAR(255) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `billing_postal` VARCHAR(20) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `billing_city` VARCHAR(100) NOT NULL DEFAULT \'\'');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `billing_country` CHAR(2) NOT NULL DEFAULT \'FR\'');

            $pdo->exec(<<<'SQL'
                UPDATE `orders` SET
                    `billing_name` = `shipping_name`,
                    `billing_line1` = `shipping_line1`,
                    `billing_postal` = `shipping_postal`,
                    `billing_city` = `shipping_city`,
                    `billing_country` = `shipping_country`
                WHERE `billing_name` = '' AND `shipping_name` != ''
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return;
        }

        foreach ([
            'shipping_name', 'shipping_line1', 'shipping_postal', 'shipping_city', 'shipping_country',
            'billing_same_as_shipping', 'billing_name', 'billing_line1', 'billing_postal', 'billing_city', 'billing_country',
        ] as $col) {
            $pdo->exec("ALTER TABLE `users` DROP COLUMN `{$col}`");
        }

        foreach (['billing_name', 'billing_line1', 'billing_postal', 'billing_city', 'billing_country'] as $col) {
            $pdo->exec("ALTER TABLE `orders` DROP COLUMN `{$col}`");
        }
    }
}
