<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;

final class Order
{
    public const PAYMENT_CARD     = 'card';
    public const PAYMENT_TRANSFER = 'transfer';
    public const PAYMENT_CHECK    = 'check';

    public const FULFILLMENT_AWAITING  = 'awaiting';
    public const FULFILLMENT_PREPARING = 'preparing';
    public const FULFILLMENT_SHIPPED   = 'shipped';

    public int $id = 0;
    public int $user_id = 0;
    public int $subtotal_cents = 0;
    public int $shipping_cents = 0;
    public int $total_cents = 0;
    public string $currency = 'eur';
    public ?string $invoice_path = null;

    /**
     * Statut PAIEMENT — contrat astral-payment (webhook) + cancelled (abandon boutique).
     * pending | paid | failed | refunded | cancelled
     */
    public string $status = 'pending';

    public const STATUS_CANCELLED = 'cancelled';

    /** card | transfer | check */
    public string $payment_method = self::PAYMENT_CARD;

    /** awaiting | preparing | shipped */
    public string $fulfillment_status = self::FULFILLMENT_AWAITING;

    public ?string $stripe_intent_id = null;
    public string $shipping_name = '';
    public string $shipping_line1 = '';
    public string $shipping_postal = '';
    public string $shipping_city = '';
    public string $shipping_country = 'FR';

    /** Snapshot facturation (peut différer de la livraison). */
    public string $billing_name = '';
    public string $billing_line1 = '';
    public string $billing_postal = '';
    public string $billing_city = '';
    public string $billing_country = 'FR';

    public int|string $stock_applied = 0;

    /** Échéance paiement offline (virement / chèque), null pour carte. */
    public ?string $payment_due_at = null;

    public string $created_at = '';

    public function isOfflinePayment(): bool
    {
        return in_array($this->payment_method, [self::PAYMENT_TRANSFER, self::PAYMENT_CHECK], true);
    }

    public function hasStockApplied(): bool
    {
        return (int) ($this->stock_applied ?? 0) === 1;
    }

    /** Jointure admin (users.name / users.email). */
    public ?string $user_name = null;
    public ?string $user_email = null;

    /** Jointure order_shipments.method_code (liste / détail). */
    public ?string $shipping_method_code = null;

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /** Commande encore payable / annulable (non payée, non annulée). */
    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'failed'], true);
    }

    public function totalLabel(string $symbol = '€'): string
    {
        return Money::formatCents($this->total_cents, $symbol);
    }

    public function subtotalLabel(string $symbol = '€'): string
    {
        return Money::formatCents($this->subtotal_cents, $symbol);
    }

    public function shippingLabel(string $symbol = '€'): string
    {
        return $this->shipping_cents === 0
            ? 'Gratuit'
            : Money::formatCents($this->shipping_cents, $symbol);
    }

    public function hasInvoice(): bool
    {
        return $this->invoice_path !== null && trim($this->invoice_path) !== '';
    }

    /** Libellé paiement à côté de la date (client). */
    public function paymentStateLabel(): string
    {
        return match ($this->status) {
            'paid'      => 'Payée',
            'pending'   => 'En attente de paiement',
            'failed'    => 'Paiement échoué',
            'refunded'  => 'Remboursée',
            'cancelled' => 'Annulée',
            default     => $this->status,
        };
    }

    public function paymentStateBadgeClass(): string
    {
        return match ($this->status) {
            'paid'      => 'status-badge status-badge--paid',
            'pending'   => 'status-badge status-badge--pending',
            'failed'    => 'status-badge status-badge--failed',
            'refunded'  => 'status-badge status-badge--refunded',
            'cancelled' => 'status-badge status-badge--refunded',
            default     => 'status-badge',
        };
    }

    public function isStorePickup(): bool
    {
        return ($this->shipping_method_code ?? '') === 'store_pickup';
    }

    /** Statut colis (client / admin) — libellé adapté au retrait boutique. */
    public function fulfillmentLabel(): string
    {
        return match ($this->fulfillment_status) {
            self::FULFILLMENT_PREPARING => 'En préparation',
            self::FULFILLMENT_SHIPPED   => $this->isStorePickup() ? 'Retiré en boutique' : 'Envoyée',
            default                     => 'En attente',
        };
    }

    public function fulfillmentBadgeClass(): string
    {
        return match ($this->fulfillment_status) {
            self::FULFILLMENT_PREPARING => 'status-badge status-badge--preparing',
            self::FULFILLMENT_SHIPPED   => $this->isStorePickup()
                ? 'status-badge status-badge--picked-up'
                : 'status-badge status-badge--shipped',
            default                     => 'status-badge status-badge--awaiting',
        };
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_TRANSFER => 'Virement',
            self::PAYMENT_CHECK    => 'Chèque',
            default                => 'Carte',
        };
    }

    /** Référence à indiquer pour virement / chèque (ex. CMD-12). */
    public function paymentReference(): ?string
    {
        if (!in_array($this->payment_method, [self::PAYMENT_TRANSFER, self::PAYMENT_CHECK], true)) {
            return null;
        }

        return 'CMD-' . $this->id;
    }

    /** Adresse facturation effective (fallback livraison pour anciennes commandes). */
    public function billingName(): string
    {
        return $this->billing_name !== '' ? $this->billing_name : $this->shipping_name;
    }

    public function billingLine1(): string
    {
        return $this->billing_line1 !== '' ? $this->billing_line1 : $this->shipping_line1;
    }

    public function billingPostal(): string
    {
        return $this->billing_postal !== '' ? $this->billing_postal : $this->shipping_postal;
    }

    public function billingCity(): string
    {
        return $this->billing_city !== '' ? $this->billing_city : $this->shipping_city;
    }

    public function billingCountry(): string
    {
        return $this->billing_country !== '' ? $this->billing_country : $this->shipping_country;
    }

    public function billingSameAsShippingSnapshot(): bool
    {
        return $this->billingName() === $this->shipping_name
            && $this->billingLine1() === $this->shipping_line1
            && $this->billingPostal() === $this->shipping_postal
            && $this->billingCity() === $this->shipping_city
            && $this->billingCountry() === $this->shipping_country;
    }

    /**
     * @deprecated Préférer paymentStateLabel() / fulfillmentLabel()
     * Conservé pour compat admin temporaire (filtre status paiement).
     */
    public function statusLabel(): string
    {
        return $this->paymentStateLabel();
    }

    /** @deprecated Préférer paymentStateBadgeClass() / fulfillmentBadgeClass() */
    public function statusBadgeClass(): string
    {
        return $this->paymentStateBadgeClass();
    }
}
