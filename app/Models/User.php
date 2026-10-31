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
    public string  $role       = Role::User->value;
    public string  $created_at = '';

    // Confirmation d'e-mail
    public ?string $email_verified_at   = null;
    public ?string $verification_token  = null;

    // Réinitialisation du mot de passe
    public ?string $password_reset_token      = null;
    public ?string $password_reset_expires_at = null;

    // Adresse de livraison (profil)
    public string $shipping_name    = '';
    public string $shipping_line1   = '';
    public string $shipping_postal    = '';
    public string $shipping_city    = '';
    public string $shipping_country = 'FR';

    /** 1 = facturation = livraison */
    public int|string $billing_same_as_shipping = 1;

    public string $billing_name    = '';
    public string $billing_line1   = '';
    public string $billing_postal    = '';
    public string $billing_city    = '';
    public string $billing_country = 'FR';

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
        return $this->role === Role::Admin->value;
    }

    public function isUser(): bool
    {
        return $this->role === Role::User->value;
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

    public function billingSameAsShipping(): bool
    {
        return (int) ($this->billing_same_as_shipping ?? 1) === 1;
    }

    public function hasShippingAddress(): bool
    {
        return trim($this->shipping_line1) !== ''
            && trim($this->shipping_postal) !== ''
            && trim($this->shipping_city) !== '';
    }

    /**
     * Adresse livraison pour préremplir le checkout.
     *
     * @return array{
     *   shipping_name: string,
     *   shipping_line1: string,
     *   shipping_postal: string,
     *   shipping_city: string,
     *   shipping_country: string
     * }
     */
    public function shippingAddressArray(): array
    {
        return [
            'shipping_name'    => $this->shipping_name !== '' ? $this->shipping_name : $this->name,
            'shipping_line1'   => $this->shipping_line1,
            'shipping_postal'    => $this->shipping_postal,
            'shipping_city'    => $this->shipping_city,
            'shipping_country' => $this->shipping_country !== '' ? $this->shipping_country : 'FR',
        ];
    }

    /**
     * Adresse facturation effective (copie livraison si flag).
     *
     * @return array{
     *   billing_name: string,
     *   billing_line1: string,
     *   billing_postal: string,
     *   billing_city: string,
     *   billing_country: string
     * }
     */
    public function billingAddressArray(): array
    {
        if ($this->billingSameAsShipping() || !$this->hasBillingAddress()) {
            $s = $this->shippingAddressArray();

            return [
                'billing_name'    => $s['shipping_name'],
                'billing_line1'   => $s['shipping_line1'],
                'billing_postal'    => $s['shipping_postal'],
                'billing_city'    => $s['shipping_city'],
                'billing_country' => $s['shipping_country'],
            ];
        }

        return [
            'billing_name'    => $this->billing_name !== '' ? $this->billing_name : $this->name,
            'billing_line1'   => $this->billing_line1,
            'billing_postal'    => $this->billing_postal,
            'billing_city'    => $this->billing_city,
            'billing_country' => $this->billing_country !== '' ? $this->billing_country : 'FR',
        ];
    }

    public function hasBillingAddress(): bool
    {
        return trim($this->billing_line1) !== ''
            && trim($this->billing_postal) !== ''
            && trim($this->billing_city) !== '';
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
