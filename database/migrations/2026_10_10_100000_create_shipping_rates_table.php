<?php
declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Barèmes de frais de port : méthode × pays × tranche de poids (grammes, bornes inclusives).
 * country_code = '*' : tous pays. Montants en centimes.
 */
class CreateShippingRatesTable extends Migration
{
    public function up(\PDO $pdo): void
    {
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS shipping_rates (
                    id             INTEGER PRIMARY KEY,
                    method_code    TEXT    NOT NULL,
                    country_code   TEXT    NOT NULL DEFAULT '*',
                    min_weight_g   INTEGER NOT NULL DEFAULT 0,
                    max_weight_g   INTEGER NOT NULL,
                    price_cents    INTEGER NOT NULL,
                    estimated_days INTEGER NULL,
                    active         INTEGER NOT NULL DEFAULT 1
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_shipping_rates_lookup ON shipping_rates (method_code, country_code)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `shipping_rates` (
                    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `method_code`    VARCHAR(64)  NOT NULL,
                    `country_code`   VARCHAR(2)   NOT NULL DEFAULT '*',
                    `min_weight_g`   INT UNSIGNED NOT NULL DEFAULT 0,
                    `max_weight_g`   INT UNSIGNED NOT NULL,
                    `price_cents`    INT UNSIGNED NOT NULL,
                    `estimated_days` SMALLINT UNSIGNED NULL,
                    `active`         TINYINT(1)   NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    KEY `idx_shipping_rates_lookup` (`method_code`, `country_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS shipping_rates');
    }
}
