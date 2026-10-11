<?php

declare(strict_types=1);

use Database\Migration\Migration;

class CreateOrderItemsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS order_items (
                    id                 INTEGER PRIMARY KEY,
                    order_id           INTEGER NOT NULL,
                    product_id         INTEGER NULL,
                    product_name       TEXT    NOT NULL,
                    unit_price_cents   INTEGER NOT NULL,
                    quantity           INTEGER NOT NULL,
                    line_total_cents   INTEGER NOT NULL,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items (order_id)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `order_items` (
                    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id`         INT UNSIGNED NOT NULL,
                    `product_id`       INT UNSIGNED NULL,
                    `product_name`     VARCHAR(200) NOT NULL,
                    `unit_price_cents` INT UNSIGNED NOT NULL,
                    `quantity`         INT UNSIGNED NOT NULL,
                    `line_total_cents` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_order_items_order` (`order_id`),
                    CONSTRAINT `order_items_order_fk`
                        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS order_items');
    }
}
