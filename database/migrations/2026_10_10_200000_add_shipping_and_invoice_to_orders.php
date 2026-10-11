<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Sous-total / port / chemin facture PDF.
 */
class AddShippingAndInvoiceToOrders extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE orders ADD COLUMN subtotal_cents INTEGER NOT NULL DEFAULT 0');
            $pdo->exec('ALTER TABLE orders ADD COLUMN shipping_cents INTEGER NOT NULL DEFAULT 0');
            $pdo->exec('ALTER TABLE orders ADD COLUMN invoice_path TEXT NULL');
            // Rétrocompat : total existant = sous-total (port 0)
            $pdo->exec('UPDATE orders SET subtotal_cents = total_cents WHERE subtotal_cents = 0 AND total_cents > 0');
        } else {
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `subtotal_cents` INT UNSIGNED NOT NULL DEFAULT 0');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `shipping_cents` INT UNSIGNED NOT NULL DEFAULT 0');
            $pdo->exec('ALTER TABLE `orders` ADD COLUMN `invoice_path` VARCHAR(255) NULL');
            $pdo->exec('UPDATE `orders` SET `subtotal_cents` = `total_cents` WHERE `subtotal_cents` = 0 AND `total_cents` > 0');
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `subtotal_cents`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `shipping_cents`');
            $pdo->exec('ALTER TABLE `orders` DROP COLUMN `invoice_path`');
        }
    }
}
