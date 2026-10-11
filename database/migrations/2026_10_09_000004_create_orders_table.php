<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Aligné sur astral-payment (orders) + champs livraison shop.
 */
class CreateOrdersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS orders (
                    id                INTEGER PRIMARY KEY,
                    user_id           INTEGER NOT NULL,
                    total_cents       INTEGER NOT NULL DEFAULT 0,
                    currency          TEXT    NOT NULL DEFAULT 'eur',
                    status            TEXT    NOT NULL DEFAULT 'pending',
                    stripe_intent_id  TEXT    NULL UNIQUE,
                    shipping_name     TEXT    NOT NULL DEFAULT '',
                    shipping_line1    TEXT    NOT NULL DEFAULT '',
                    shipping_postal     TEXT    NOT NULL DEFAULT '',
                    shipping_city     TEXT    NOT NULL DEFAULT '',
                    shipping_country  TEXT    NOT NULL DEFAULT 'FR',
                    created_at        TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders (user_id)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `orders` (
                    `id`                INT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `user_id`           INT UNSIGNED   NOT NULL,
                    `total_cents`       INT UNSIGNED   NOT NULL DEFAULT 0,
                    `currency`          VARCHAR(3)     NOT NULL DEFAULT 'eur',
                    `status`            VARCHAR(20)    NOT NULL DEFAULT 'pending',
                    `stripe_intent_id`  VARCHAR(255)   NULL,
                    `shipping_name`     VARCHAR(150)   NOT NULL DEFAULT '',
                    `shipping_line1`    VARCHAR(255)   NOT NULL DEFAULT '',
                    `shipping_postal`     VARCHAR(20)    NOT NULL DEFAULT '',
                    `shipping_city`     VARCHAR(100)   NOT NULL DEFAULT '',
                    `shipping_country`  VARCHAR(2)     NOT NULL DEFAULT 'FR',
                    `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_orders_user_id` (`user_id`),
                    KEY `idx_orders_status` (`status`),
                    UNIQUE KEY `uk_orders_intent` (`stripe_intent_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS orders');
    }
}
