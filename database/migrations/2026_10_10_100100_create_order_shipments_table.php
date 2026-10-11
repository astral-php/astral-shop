<?php
declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Snapshot du mode de livraison d'une commande : on ne recalcule jamais le port a posteriori.
 */
class CreateOrderShipmentsTable extends Migration
{
    public function up(\PDO $pdo): void
    {
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS order_shipments (
                    id                 INTEGER PRIMARY KEY,
                    order_id           INTEGER NOT NULL,
                    method_code        TEXT    NOT NULL,
                    carrier            TEXT    NOT NULL,
                    label              TEXT    NOT NULL,
                    price_cents        INTEGER NOT NULL,
                    currency           TEXT    NOT NULL DEFAULT 'EUR',
                    pickup_point_id    TEXT    NULL,
                    pickup_point_label TEXT    NULL,
                    tracking_number    TEXT    NULL,
                    status             TEXT    NOT NULL DEFAULT 'pending',
                    created_at         TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_order_shipments_order ON order_shipments (order_id)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `order_shipments` (
                    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id`           INT UNSIGNED NOT NULL,
                    `method_code`        VARCHAR(64)  NOT NULL,
                    `carrier`            VARCHAR(64)  NOT NULL,
                    `label`              VARCHAR(255) NOT NULL,
                    `price_cents`        INT UNSIGNED NOT NULL,
                    `currency`           CHAR(3)      NOT NULL DEFAULT 'EUR',
                    `pickup_point_id`    VARCHAR(64)  NULL,
                    `pickup_point_label` VARCHAR(255) NULL,
                    `tracking_number`    VARCHAR(128) NULL,
                    `status`             VARCHAR(32)  NOT NULL DEFAULT 'pending',
                    `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_order_shipments_order` (`order_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS order_shipments');
    }
}
