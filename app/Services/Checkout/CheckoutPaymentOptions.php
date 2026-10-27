<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Models\Order;
use AstralPaypal\PaypalService;

/**
 * Moyens de paiement activés pour le tunnel (config shop + PayPal configuré).
 * L’initiation Stripe/PayPal reste dans PaymentCheckoutController / PaypalCheckoutController.
 */
final class CheckoutPaymentOptions
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        private array $shop,
        private PaypalService $paypal,
    ) {}

    /** @return list<string> */
    public function enabledMethods(): array
    {
        $configured = $this->shop['payment']['methods'] ?? ['card', 'transfer', 'check'];
        if (!is_array($configured)) {
            return [Order::PAYMENT_CARD];
        }

        $allowed = [
            Order::PAYMENT_CARD,
            Order::PAYMENT_PAYPAL,
            Order::PAYMENT_TRANSFER,
            Order::PAYMENT_CHECK,
        ];
        $out = [];
        foreach ($configured as $m) {
            $m = (string) $m;
            if (!in_array($m, $allowed, true)) {
                continue;
            }
            if ($m === Order::PAYMENT_PAYPAL && !$this->paypal->isConfigured()) {
                continue;
            }
            $out[] = $m;
        }

        return $out !== [] ? $out : [Order::PAYMENT_CARD];
    }
}
