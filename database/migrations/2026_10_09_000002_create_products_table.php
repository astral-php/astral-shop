<?php

declare(strict_types=1);

use Database\Migration\Migration;

class CreateProductsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS products (
                    id           INTEGER PRIMARY KEY,
                    category_id  INTEGER NULL,
                    name         TEXT    NOT NULL,
                    slug         TEXT    NOT NULL UNIQUE,
                    description  TEXT    NULL,
                    price_cents  INTEGER NOT NULL,
                    currency     TEXT    NOT NULL DEFAULT 'EUR',
                    stock        INTEGER NOT NULL DEFAULT 0,
                    sku          TEXT    NULL UNIQUE,
                    image_path   TEXT    NULL,
                    image_url    TEXT    NULL,
                    active       INTEGER NOT NULL DEFAULT 1,
                    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                    updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS products_active_idx ON products (active)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS products_category_idx ON products (category_id)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `products` (
                    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                    `category_id`  INT UNSIGNED  NULL,
                    `name`         VARCHAR(200)  NOT NULL,
                    `slug`         VARCHAR(220)  NOT NULL,
                    `description`  TEXT          NULL,
                    `price_cents`  INT UNSIGNED  NOT NULL,
                    `currency`     CHAR(3)       NOT NULL DEFAULT 'EUR',
                    `stock`        INT UNSIGNED  NOT NULL DEFAULT 0,
                    `sku`          VARCHAR(64)   NULL,
                    `image_path`   VARCHAR(255)  NULL,
                    `image_url`    VARCHAR(500)  NULL,
                    `active`       TINYINT(1)    NOT NULL DEFAULT 1,
                    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `products_slug_unique` (`slug`),
                    UNIQUE KEY `products_sku_unique` (`sku`),
                    KEY `products_active_idx` (`active`),
                    KEY `products_category_idx` (`category_id`),
                    CONSTRAINT `products_category_fk`
                        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS products');
    }
}
