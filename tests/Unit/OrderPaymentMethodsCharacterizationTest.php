<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

/**
 * Caractérisation Phase 0 — contrats payment_method shop 1.1.1.
 * Filet avant refactor checkout / worker (1.5).
 */
final class OrderPaymentMethodsCharacterizationTest extends TestCase
{
    public function test_online_and_offline_payment_constants(): void
    {
        $this->assertSame('card', Order::PAYMENT_CARD);
        $this->assertSame('paypal', Order::PAYMENT_PAYPAL);
        $this->assertSame('transfer', Order::PAYMENT_TRANSFER);
        $this->assertSame('check', Order::PAYMENT_CHECK);
    }

    public function test_offline_helper_excludes_card_and_paypal(): void
    {
        $card = new Order();
        $card->payment_method = Order::PAYMENT_CARD;
        $this->assertFalse($card->isOfflinePayment());

        $paypal = new Order();
        $paypal->payment_method = Order::PAYMENT_PAYPAL;
        $this->assertFalse($paypal->isOfflinePayment());

        $transfer = new Order();
        $transfer->payment_method = Order::PAYMENT_TRANSFER;
        $this->assertTrue($transfer->isOfflinePayment());
    }

    public function test_payment_method_labels(): void
    {
        $order = new Order();
        $order->payment_method = Order::PAYMENT_PAYPAL;
        $this->assertSame('PayPal', $order->paymentMethodLabel());

        $order->payment_method = Order::PAYMENT_CARD;
        $this->assertSame('Carte', $order->paymentMethodLabel());
    }
}
