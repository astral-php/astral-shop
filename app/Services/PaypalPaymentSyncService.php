<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Events\OrderBecamePaid;
use App\Models\Order;
use AstralPaypal\Events\PaymentSucceeded as PaypalPaymentSucceeded;
use AstralPaypal\PaypalService;
use Core\Events\EventDispatcher;
use Core\Logger;
use Throwable;

/**
 * Capture / confirme une commande PayPal (return URL ou filet anti-race webhook).
 */
final class PaypalPaymentSyncService
{
    public function __construct(
        private OrderDao $orderDao,
        private PaypalService $paypal,
        private EventDispatcher $dispatcher,
        private Logger $logger,
    ) {
    }

    /**
     * Capture si APPROVED, ou marque paid si déjà COMPLETED.
     */
    public function captureAndConfirm(Order $order): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        if ($order->payment_method !== Order::PAYMENT_PAYPAL) {
            return $order;
        }

        $paypalOrderId = trim((string) ($order->paypal_order_id ?? ''));
        if ($paypalOrderId === '') {
            return $order;
        }

        try {
            $remote = $this->paypal->showOrder($paypalOrderId);
            if ($remote->isApproved()) {
                $remote = $this->paypal->captureOrder($paypalOrderId);
            }
        } catch (Throwable $e) {
            $this->logger->warning('PaypalPaymentSync: capture/show échoué', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);

            return $order;
        }

        if (!$remote->isCompleted()) {
            return $order;
        }

        return $this->markPaid($order, $paypalOrderId, $remote->captureId, $order->total_cents);
    }

    public function markPaid(
        Order $order,
        string $paypalOrderId,
        ?string $captureId,
        int $amountCents,
    ): Order {
        $extra = ['paypal_order_id' => $paypalOrderId];
        if ($captureId !== null && $captureId !== '') {
            $extra['paypal_capture_id'] = $captureId;
        }

        // Claim SQL unique (return URL vs webhook) — pas d’événements en double
        if (!$this->orderDao->tryMarkPaid($order->id, $extra)) {
            return $this->orderDao->findById($order->id) ?? $order;
        }

        $this->dispatcher->dispatch(new OrderBecamePaid($order->id));
        $this->dispatcher->dispatch(new PaypalPaymentSucceeded(
            shopOrderReference: (string) $order->id,
            paypalOrderId: $paypalOrderId,
            captureId: $captureId,
            amountCents: $amountCents,
            currency: strtoupper($order->currency ?: 'EUR'),
        ));

        $this->logger->info('PaypalPaymentSync: commande confirmée', [
            'order_id'        => $order->id,
            'paypal_order_id' => $paypalOrderId,
            'capture_id'      => $captureId,
        ]);

        return $this->orderDao->findById($order->id) ?? $order;
    }

    public function markFailed(Order $order, string $reason = ''): Order
    {
        if ($order->isPaid() || $order->isCancelled()) {
            return $order;
        }

        $this->orderDao->update($order->id, ['status' => 'failed']);
        $this->logger->warning('PaypalPaymentSync: paiement échoué', [
            'order_id' => $order->id,
            'reason'   => $reason,
        ]);

        return $this->orderDao->findById($order->id) ?? $order;
    }
}
