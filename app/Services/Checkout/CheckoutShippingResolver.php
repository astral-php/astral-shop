<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Services\CartService;
use AstralShipping\Exception\InvalidSelectionException;
use AstralShipping\Models\Address;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingSelection;
use AstralShipping\Service\ShippingService;

/**
 * Contexte devis + résolution de la sélection livraison depuis panier / session.
 */
final class CheckoutShippingResolver
{
    public function __construct(
        private CartService $cart,
        private ShippingService $shippingService,
        private CheckoutSessionStore $sessionStore,
    ) {}

    /**
     * @param array<string, mixed> $address session shipping
     */
    public function context(array $address): ShippingContext
    {
        $weightGrams = 0;
        foreach ($this->cart->lines() as $line) {
            $product = $line['product'] ?? null;
            if ($product === null) {
                continue;
            }
            $weightGrams += max(0, (int) $product->weight_g) * max(1, (int) $line['qty']);
        }

        return new ShippingContext(
            subtotalCents: $this->cart->totalCents(),
            weightGrams: $weightGrams,
            destination: new Address(
                country: (string) ($address['shipping_country'] ?? 'FR'),
                postalCode: (string) ($address['shipping_postal'] ?? ''),
                city: (string) ($address['shipping_city'] ?? ''),
                line1: (string) ($address['shipping_line1'] ?? ''),
            ),
        );
    }

    public function resolveSelection(): ?ShippingSelection
    {
        $address = $this->sessionStore->shipping();
        $code = $this->sessionStore->methodCode();
        if ($address === [] || $code === '') {
            return null;
        }

        $pickup = $this->sessionStore->pickup();
        $pickupId = trim((string) ($pickup['id'] ?? ''));
        $pickupLabel = trim((string) ($pickup['label'] ?? ''));

        try {
            return $this->shippingService->select(
                $this->context($address),
                $code,
                $pickupId !== '' ? $pickupId : null,
                $pickupLabel !== '' ? $pickupLabel : null,
            );
        } catch (InvalidSelectionException) {
            $this->sessionStore->forgetMethod();
            $this->sessionStore->setPickup(null);

            return null;
        }
    }

    /**
     * @throws InvalidSelectionException
     */
    public function select(
        array $address,
        string $code,
        ?string $pickupId,
        ?string $pickupLabel,
    ): ShippingSelection {
        return $this->shippingService->select(
            $this->context($address),
            $code,
            $pickupId,
            $pickupLabel,
        );
    }

    /** @return list<\AstralShipping\Models\ShippingQuote> */
    public function quotes(array $address): array
    {
        return $this->shippingService->quotes($this->context($address));
    }

    public function total(int $subtotalCents, ShippingSelection $selection): int
    {
        return $this->shippingService->total($subtotalCents, $selection);
    }
}
