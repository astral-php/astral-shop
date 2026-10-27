<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use Core\Session;

/**
 * Clés session du tunnel checkout (source unique).
 */
final class CheckoutSessionStore
{
    public const SHIPPING = 'checkout.shipping';
    public const BILLING = 'checkout.billing';
    public const METHOD = 'checkout.shipping_method';
    public const PICKUP = 'checkout.pickup_point';
    public const PENDING = 'checkout.pending_order_id';
    public const PAYMENT_METHOD = 'checkout.payment_method';

    public function __construct(private Session $session) {}

    /** @return array<string, mixed> */
    public function shipping(): array
    {
        $data = $this->session->get(self::SHIPPING, []);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $shipping */
    public function setShipping(array $shipping): void
    {
        $this->session->set(self::SHIPPING, $shipping);
    }

    /** @return array<string, mixed> */
    public function billing(): array
    {
        $data = $this->session->get(self::BILLING, []);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $billing */
    public function setBilling(array $billing): void
    {
        $this->session->set(self::BILLING, $billing);
    }

    public function methodCode(): string
    {
        return trim((string) $this->session->get(self::METHOD, ''));
    }

    public function setMethodCode(string $code): void
    {
        $this->session->set(self::METHOD, $code);
    }

    public function forgetMethod(): void
    {
        $this->session->forget(self::METHOD);
    }

    /** @return array{id?: string, label?: string} */
    public function pickup(): array
    {
        $data = $this->session->get(self::PICKUP, []);

        return is_array($data) ? $data : [];
    }

    /** @param array{id: string, label: string}|null $pickup */
    public function setPickup(?array $pickup): void
    {
        if ($pickup === null) {
            $this->session->forget(self::PICKUP);

            return;
        }

        $this->session->set(self::PICKUP, $pickup);
    }

    public function pendingOrderId(): int
    {
        return (int) $this->session->get(self::PENDING, 0);
    }

    public function setPendingOrderId(int $orderId): void
    {
        $this->session->set(self::PENDING, $orderId);
    }

    public function forgetPending(): void
    {
        $this->session->forget(self::PENDING);
    }

    public function paymentMethod(): string
    {
        return (string) $this->session->get(self::PAYMENT_METHOD, '');
    }

    public function setPaymentMethod(string $method): void
    {
        $this->session->set(self::PAYMENT_METHOD, $method);
    }

    public function clear(): void
    {
        $this->session->forget(self::SHIPPING);
        $this->session->forget(self::BILLING);
        $this->session->forget(self::METHOD);
        $this->session->forget(self::PICKUP);
        $this->session->forget(self::PENDING);
        $this->session->forget(self::PAYMENT_METHOD);
    }
}
