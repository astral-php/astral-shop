<?php

declare(strict_types=1);

namespace App\Models;

use Core\Auth\Role;

/**
 * Modèle User.
 *
 * Représentation anémique d'un utilisateur en base.
 * Les propriétés sont publiques pour permettre l'hydratation
 * via PDO::FETCH_CLASS (PDO assigne les colonnes avant __construct).
 */
class User
{
    public int     $id         = 0;
    public string  $name       = '';
    public string  $email      = '';
    public string  $password   = '';
    public string  $role       = Role::USER;
    public string  $created_at = '';

    // Confirmation d'e-mail
    public ?string $email_verified_at   = null;
    public ?string $verification_token  = null;

    // Réinitialisation du mot de passe
    public ?string $password_reset_token      = null;
    public ?string $password_reset_expires_at = null;

    // -------------------------------------------------------------------------
    // Helpers de rôle
    // -------------------------------------------------------------------------

    /** L'adresse e-mail a-t-elle été vérifiée ? */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === Role::USER;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    // -------------------------------------------------------------------------
    // Utilitaires
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        return "{$this->name} <{$this->email}>";
    }

    public function isPersisted(): bool
    {
        return $this->id > 0;
    }

    /**
     * Retourne les données sérialisables (sans le mot de passe).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'created_at' => $this->created_at,
        ];
    }
}
