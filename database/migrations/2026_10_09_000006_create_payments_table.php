<?php
declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Migration : create_payments_table
 */
class CreatePaymentsTable extends Migration
{
    public function up(\PDO $pdo): void
    {
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS payments (
                    id                INTEGER  PRIMARY KEY AUTOINCREMENT,
                    order_id          INTEGER  NOT NULL,
                    stripe_intent_id  TEXT     NOT NULL,
                    amount_cents      INTEGER  NOT NULL DEFAULT 0,
                    currency          TEXT     NOT NULL DEFAULT 'eur',
                    status            TEXT     NOT NULL DEFAULT 'pending',
                    created_at        TEXT     NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `payments` (
                    `id`                INT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `order_id`          INT UNSIGNED   NOT NULL,
                    `stripe_intent_id`  VARCHAR(255)   NOT NULL,
                    `amount_cents`      INT UNSIGNED   NOT NULL DEFAULT 0,
                    `currency`          VARCHAR(3)     NOT NULL DEFAULT 'eur',
                    `status`            VARCHAR(20)    NOT NULL DEFAULT 'pending'
                                        COMMENT 'pending|succeeded|failed|refunded',
                    `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_payments_order_id` (`order_id`),
                    KEY `idx_payments_intent`   (`stripe_intent_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS payments');
    }
}
