<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Events\OrderBecamePaid;
use App\Models\Order;
use AstralPayment\PaymentService;
use Core\Events\EventDispatcher;
use Core\Logger;
use Throwable;

/**
 * Confirme une commande carte si le PaymentIntent Stripe est succeeded.
 * Filet de sécurité quand le webhook local n’arrive pas (CLI / HTTPS).
 */
final class StripePaymentSyncService
{
    public function __construct(
        private OrderDao $orderDao,
        private PaymentService $paymentService,
        private EventDispatcher $dispatcher,
        private Logger $logger,
    ) {}

    public function confirmIfSucceeded(Order $order): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        if ($order->payment_method !== Order::PAYMENT_CARD) {
            return $order;
        }

        $intentId = trim((string) $order->stripe_intent_id);
        if ($intentId === '') {
            return $order;
        }

        try {
            $intent = $this->paymentService->retrievePaymentIntent($intentId);
        } catch (Throwable $e) {
            $this->logger->warning('StripePaymentSync: retrieve PI échoué', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);
            return $order;
        }

        if (($intent->status ?? '') !== 'succeeded') {
            return $order;
        }

        // Course possible avec le webhook
        $fresh = $this->orderDao->findById($order->id);
        if ($fresh !== null && $fresh->isPaid()) {
            return $fresh;
        }

        $this->orderDao->update($order->id, ['status' => 'paid']);
        $this->dispatcher->dispatch(new OrderBecamePaid($order->id));

        $this->logger->info('StripePaymentSync: commande confirmée via API (retour / poll)', [
            'order_id'  => $order->id,
            'intent_id' => $intentId,
        ]);

        return $this->orderDao->findById($order->id) ?? $order;
    }
}
