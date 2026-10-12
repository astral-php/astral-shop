<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\User;
use Core\Auth\Role;
use Database\AbstractDao;
use Database\Exception\DatabaseException;

/**
 * DAO pour l'entité User.
 *
 * Hérite des opérations CRUD génériques d'AbstractDao
 * et ajoute des méthodes métier propres à l'utilisateur.
 *
 * @extends AbstractDao<User>
 */
final class UserDao extends AbstractDao
{
    protected function getTable(): string
    {
        return 'users';
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

    // -------------------------------------------------------------------------
    // Méthodes de recherche
    // -------------------------------------------------------------------------

    public function findByEmail(string $email): ?User
    {
        $rows = $this->query(
            sql:    "SELECT * FROM {$this->getTable()} WHERE email = :email LIMIT 1",
            params: [':email' => $email],
        );

        /** @var User|null */
        return $rows[0] ?? null;
    }

    /**
     * Retourne tous les utilisateurs ayant ce rôle.
     *
     * @return list<User>
     */
    public function findByRole(string $role): array
    {
        /** @var list<User> */
        return $this->query(
            sql:    "SELECT * FROM {$this->getTable()} WHERE role = :role ORDER BY name",
            params: [':role' => $role],
        );
    }

    // -------------------------------------------------------------------------
    // Création / mise à jour
    // -------------------------------------------------------------------------

    /**
     * Crée un utilisateur avec un mot de passe hashé.
     * Retourne l'ID de l'enregistrement créé.
     */
    public function createUser(
        string $name,
        string $email,
        string $plainPassword,
        string $role = Role::USER,
    ): int {
        if ($this->findByEmail($email) !== null) {
            throw new DatabaseException("L'adresse e-mail {$email} est déjà utilisée.");
        }

        // Premier admin : uniquement via /install (role ADMIN explicite).
        // L'inscription publique crée toujours un client (USER).

        return $this->insert([
            'name'       => $name,
            'email'      => $email,
            'password'   => password_hash($plainPassword, PASSWORD_BCRYPT),
            'role'       => Role::isValid($role) && $role !== Role::GUEST ? $role : Role::USER,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Met à jour le nom et l'e-mail d'un utilisateur.
     *
     * @throws DatabaseException si l'e-mail est déjà utilisé par un autre compte
     */
    public function updateProfile(int $userId, string $name, string $email): void
    {
        $existing = $this->findByEmail($email);

        if ($existing !== null && $existing->id !== $userId) {
            throw new DatabaseException("L'adresse e-mail {$email} est déjà utilisée.");
        }

        $this->pdo->prepare(
            "UPDATE {$this->getTable()} SET name = :name, email = :email WHERE id = :id"
        )->execute([':name' => $name, ':email' => $email, ':id' => $userId]);
    }

    /**
     * Enregistre les adresses livraison / facturation du profil.
     *
     * @param array{
     *   shipping_name: string,
     *   shipping_line1: string,
     *   shipping_postal: string,
     *   shipping_city: string,
     *   shipping_country: string,
     *   billing_same_as_shipping: int,
     *   billing_name: string,
     *   billing_line1: string,
     *   billing_postal: string,
     *   billing_city: string,
     *   billing_country: string
     * } $addresses
     */
    public function updateAddresses(int $userId, array $addresses): void
    {
        $this->pdo->prepare(
            "UPDATE {$this->getTable()} SET
                shipping_name = :shipping_name,
                shipping_line1 = :shipping_line1,
                shipping_postal = :shipping_postal,
                shipping_city = :shipping_city,
                shipping_country = :shipping_country,
                billing_same_as_shipping = :billing_same_as_shipping,
                billing_name = :billing_name,
                billing_line1 = :billing_line1,
                billing_postal = :billing_postal,
                billing_city = :billing_city,
                billing_country = :billing_country
             WHERE id = :id"
        )->execute([
            ':shipping_name'             => $addresses['shipping_name'],
            ':shipping_line1'            => $addresses['shipping_line1'],
            ':shipping_postal'             => $addresses['shipping_postal'],
            ':shipping_city'             => $addresses['shipping_city'],
            ':shipping_country'          => $addresses['shipping_country'],
            ':billing_same_as_shipping'  => $addresses['billing_same_as_shipping'],
            ':billing_name'              => $addresses['billing_name'],
            ':billing_line1'             => $addresses['billing_line1'],
            ':billing_postal'              => $addresses['billing_postal'],
            ':billing_city'              => $addresses['billing_city'],
            ':billing_country'           => $addresses['billing_country'],
            ':id'                        => $userId,
        ]);
    }

    /**
     * Met à jour le mot de passe d'un utilisateur.
     */
    public function updatePassword(int $userId, string $newPassword): void
    {
        $this->pdo->prepare(
            "UPDATE {$this->getTable()} SET password = :password WHERE id = :id"
        )->execute([
            ':password' => password_hash($newPassword, PASSWORD_BCRYPT),
            ':id'       => $userId,
        ]);
    }

    /**
     * Change le rôle d'un utilisateur.
     */
    public function promote(int $userId, string $role): void
    {
        if (!Role::isValid($role)) {
            throw new \InvalidArgumentException("Rôle invalide : {$role}");
        }

        $this->pdo->prepare(
            "UPDATE {$this->getTable()} SET role = :role WHERE id = :id"
        )->execute([':role' => $role, ':id' => $userId]);
    }

    // -------------------------------------------------------------------------
    // Authentification
    // -------------------------------------------------------------------------

    /**
     * Vérifie les identifiants et retourne l'utilisateur ou null.
     */
    public function authenticate(string $email, string $plainPassword): ?User
    {
        $user = $this->findByEmail($email);

        if ($user === null || !password_verify($plainPassword, $user->password)) {
            return null;
        }

        return $user;
    }

    // -------------------------------------------------------------------------
    // Schéma (SQLite)
    // -------------------------------------------------------------------------

    /**
     * Crée la table users si elle n'existe pas.
     * Inclut la colonne `role` depuis la version 2.
     */
    // -------------------------------------------------------------------------
    // Confirmation d'e-mail
    // -------------------------------------------------------------------------

    /**
     * Enregistre un token de vérification sur le compte (inscription en attente).
     */
    public function setVerificationToken(int $userId, string $token): void
    {
        $this->pdo->prepare(
            "UPDATE {$this->getTable()}
             SET verification_token = :token
             WHERE id = :id"
        )->execute([':token' => $token, ':id' => $userId]);
    }

    /**
     * Confirme l'e-mail via le token ; retourne l'utilisateur confirmé ou null.
     */
    public function verifyEmail(string $token): ?User
    {
        $rows = $this->query(
            sql:    "SELECT * FROM {$this->getTable()} WHERE verification_token = :token LIMIT 1",
            params: [':token' => $token],
        );

        /** @var User|null $user */
        $user = $rows[0] ?? null;

        if ($user === null) {
            return null;
        }

        $this->pdo->prepare(
            "UPDATE {$this->getTable()}
             SET email_verified_at = :now, verification_token = NULL
             WHERE id = :id"
        )->execute([':now' => date('Y-m-d H:i:s'), ':id' => $user->id]);

        $user->email_verified_at  = date('Y-m-d H:i:s');
        $user->verification_token = null;

        return $user;
    }

    // -------------------------------------------------------------------------
    // Réinitialisation du mot de passe
    // -------------------------------------------------------------------------

    /**
     * Génère et stocke un token de réinitialisation valable 1 heure.
     * Retourne le token ou null si l'e-mail est introuvable.
     */
    public function setPasswordResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->pdo->prepare(
            "UPDATE {$this->getTable()}
             SET password_reset_token = :token, password_reset_expires_at = :expires
             WHERE id = :id"
        )->execute([':token' => $token, ':expires' => $expiresAt, ':id' => $user->id]);

        return $token;
    }

    /**
     * Cherche un utilisateur par son token de reset (non expiré).
     */
    public function findByPasswordResetToken(string $token): ?User
    {
        $rows = $this->query(
            sql: "SELECT * FROM {$this->getTable()}
                  WHERE password_reset_token = :token
                    AND password_reset_expires_at > :now
                  LIMIT 1",
            params: [':token' => $token, ':now' => date('Y-m-d H:i:s')],
        );

        /** @var User|null */
        return $rows[0] ?? null;
    }

    /**
     * Met à jour le mot de passe et efface le token de reset.
     * Prouve aussi la possession de l'e-mail → marque le compte comme vérifié
     * (nécessaire en AUTH_REGISTRATION=confirm, sinon login bloqué après reset).
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->findByPasswordResetToken($token);

        if ($user === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $this->pdo->prepare(
            "UPDATE {$this->getTable()}
             SET password = :password,
                 password_reset_token = NULL,
                 password_reset_expires_at = NULL,
                 email_verified_at = COALESCE(email_verified_at, :now),
                 verification_token = NULL
             WHERE id = :id"
        )->execute([
            ':password' => password_hash($newPassword, PASSWORD_BCRYPT),
            ':now'      => $now,
            ':id'       => $user->id,
        ]);

        return true;
    }

    /** Marque l'e-mail comme confirmé (install admin, validation manuelle, etc.). */
    public function markEmailVerified(int $userId): void
    {
        $this->pdo->prepare(
            "UPDATE {$this->getTable()}
             SET email_verified_at = COALESCE(email_verified_at, :now),
                 verification_token = NULL
             WHERE id = :id"
        )->execute([
            ':now' => date('Y-m-d H:i:s'),
            ':id'  => $userId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Schéma (SQLite)
    // -------------------------------------------------------------------------

    /**
     * Crée la table users si elle n'existe pas.
     */
    public function createTableIfNotExists(): void
    {
        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id                        INTEGER PRIMARY KEY AUTOINCREMENT,
                name                      TEXT    NOT NULL,
                email                     TEXT    NOT NULL UNIQUE,
                password                  TEXT    NOT NULL,
                role                      TEXT    NOT NULL DEFAULT 'user',
                email_verified_at         TEXT    NULL,
                verification_token        TEXT    NULL,
                password_reset_token      TEXT    NULL,
                password_reset_expires_at TEXT    NULL,
                created_at                TEXT    NOT NULL DEFAULT (datetime('now'))
            );
        SQL);

        // Migrations douces : ajout des colonnes sur une base existante
        $migrations = [
            "ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'",
            "ALTER TABLE users ADD COLUMN email_verified_at TEXT NULL",
            "ALTER TABLE users ADD COLUMN verification_token TEXT NULL",
            "ALTER TABLE users ADD COLUMN password_reset_token TEXT NULL",
            "ALTER TABLE users ADD COLUMN password_reset_expires_at TEXT NULL",
        ];

        foreach ($migrations as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (\PDOException) {
                // Colonne déjà présente — on ignore
            }
        }
    }
}
