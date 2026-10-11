<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Migration : création de la table `users`.
 *
 * Contient toutes les colonnes nécessaires au système d'authentification
 * d'Astral MVC : connexion, rôles, vérification d'e-mail, reset de mot de passe.
 */
class CreateUsersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS users (
                    id                        INTEGER  PRIMARY KEY,
                    name                      TEXT     NOT NULL,
                    email                     TEXT     NOT NULL UNIQUE,
                    password                  TEXT     NOT NULL,
                    role                      TEXT     NOT NULL DEFAULT 'user',
                    email_verified_at         TEXT     NULL,
                    verification_token        TEXT     NULL,
                    password_reset_token      TEXT     NULL,
                    password_reset_expires_at TEXT     NULL,
                    created_at                TEXT     NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `users` (
                    `id`                        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                    `name`                      VARCHAR(100)  NOT NULL,
                    `email`                     VARCHAR(180)  NOT NULL,
                    `password`                  VARCHAR(255)  NOT NULL,
                    `role`                      VARCHAR(20)   NOT NULL DEFAULT 'user',
                    `email_verified_at`         DATETIME      NULL,
                    `verification_token`        VARCHAR(255)  NULL,
                    `password_reset_token`      VARCHAR(255)  NULL,
                    `password_reset_expires_at` DATETIME      NULL,
                    `created_at`                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS users');
    }
}
