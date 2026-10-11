<?php

declare(strict_types=1);

use Database\Migration\Migration;

class CreateCategoriesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS categories (
                    id          INTEGER PRIMARY KEY,
                    name        TEXT    NOT NULL,
                    slug        TEXT    NOT NULL UNIQUE,
                    description TEXT    NULL,
                    sort_order  INTEGER NOT NULL DEFAULT 0,
                    active      INTEGER NOT NULL DEFAULT 1,
                    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                    `name`        VARCHAR(150)  NOT NULL,
                    `slug`        VARCHAR(160)  NOT NULL,
                    `description` TEXT          NULL,
                    `sort_order`  INT           NOT NULL DEFAULT 0,
                    `active`      TINYINT(1)    NOT NULL DEFAULT 1,
                    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `categories_slug_unique` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS categories');
    }
}
