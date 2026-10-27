<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Events\OrderBecameRefunded;
use AstralPayment\Dao\PaymentDao;
use AstralPayment\Events\PaymentFailed;
use AstralPayment\Events\PaymentRefunded;
use AstralPayment\Events\PaymentSucceeded;
use AstralPayment\PaymentService;
use Core\Events\EventDispatcher;
use Core\Http\JsonResponse;
use Core\Logger;
use Stripe\Exception\SignatureVerificationException;

/**
 * Webhook Stripe — claim SQL `tryMarkPaid` avant événements (idempotent vs return URL).
 * Remplace AstralPayment\Controllers\WebhookController pour le shop.
 */
final class StripeWebhookController
{
    public function __construct(
        private PaymentService $paymentService,
        private OrderDao $orderDao,
        private PaymentDao $paymentDao,
        private EventDispatcher $dispatcher,
        private Logger $logger,
    ) {}

    public function handle(): JsonResponse
    {
        $payload   = (string) file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = $this->paymentService->parseWebhook($payload, $signature);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Webhook Stripe : signature invalide', [
                'message' => $e->getMessage(),
            ]);

            return JsonResponse::make(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            $this->logger->error('Webhook Stripe : erreur de parsing', [
                'message' => $e->getMessage(),
            ]);

            return JsonResponse::make(['error' => 'Bad request'], 400);
        }

        $this->logger->info('Webhook Stripe reçu', ['type' => $event->type]);

        match ($event->type) {
            'payment_intent.succeeded'      => $this->onPaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->onPaymentFailed($event->data->object),
            'charge.refunded'               => $this->onChargeRefunded($event->data->object),
            default                         => null,
        };

        return JsonResponse::make(['received' => true], 200);
    }

    private function onPaymentSucceeded(object $intent): void
    {
        $orderId = (int) ($intent->metadata->order_id ?? 0);
        if ($orderId < 1) {
            $this->dispatcher->dispatch(new PaymentSucceeded($intent, 0));

            return;
        }

        if (!$this->orderDao->tryMarkPaid($orderId)) {
            $this->logger->info('Webhook Stripe : paiement déjà confirmé (idempotent)', [
                'order_id' => $orderId,
            ]);

            return;
        }

        $this->paymentDao->insert([
            'order_id'         => $orderId,
            'stripe_intent_id' => $intent->id,
            'amount_cents'     => $intent->amount_received,
            'currency'         => $intent->currency,
            'status'           => 'succeeded',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->dispatcher->dispatch(new PaymentSucceeded($intent, $orderId));
    }

    private function onPaymentFailed(object $intent): void
    {
        $orderId = (int) ($intent->metadata->order_id ?? 0);

        if ($orderId > 0) {
            $order = $this->orderDao->findById($orderId);
            if ($order !== null && !$order->isPaid() && !$order->isCancelled() && !$order->isRefunded()) {
                $this->orderDao->update($orderId, ['status' => 'failed']);
            }

            $this->paymentDao->insert([
                'order_id'         => $orderId,
                'stripe_intent_id' => $intent->id,
                'amount_cents'     => $intent->amount,
                'currency'         => $intent->currency,
                'status'           => 'failed',
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        $this->dispatcher->dispatch(new PaymentFailed($intent, $orderId));
    }

    private function onChargeRefunded(object $charge): void
    {
        $this->logger->info('Charge remboursée', ['charge_id' => $charge->id ?? null]);

        $intentId = '';
        $pi = $charge->payment_intent ?? null;
        if (is_string($pi)) {
            $intentId = $pi;
        } elseif (is_object($pi) && isset($pi->id)) {
            $intentId = (string) $pi->id;
        }

        $orderId = 0;
        $transitioned = false;

        if ($intentId !== '') {
            $order = $this->orderDao->findByStripeIntentId($intentId);
            if ($order !== null) {
                $orderId = (int) $order->id;
                if (!$order->isRefunded()) {
                    $this->orderDao->update($orderId, [
                        'status'      => 'refunded',
                        'refunded_at' => date('Y-m-d H:i:s'),
                    ]);
                    $transitioned = true;
                }

                $this->paymentDao->insert([
                    'order_id'         => $orderId,
                    'stripe_intent_id' => $intentId,
                    'amount_cents'     => (int) ($charge->amount_refunded ?? $charge->amount ?? 0),
                    'currency'         => (string) ($charge->currency ?? 'eur'),
                    'status'           => 'refunded',
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($transitioned && $orderId > 0) {
            $this->dispatcher->dispatch(new PaymentRefunded($charge, $orderId));
            $this->dispatcher->dispatch(new OrderBecameRefunded($orderId));
        }
    }
}
