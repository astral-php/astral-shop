<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Events\OrderBecameRefunded;
use App\Models\Order;
use App\Services\PaypalPaymentSyncService;
use AstralPaypal\Exception\PaypalException;
use AstralPaypal\PaypalService;
use Core\Events\EventDispatcher;
use Core\Http\JsonResponse;
use Core\Logger;
use Core\Request;

/**
 * Webhooks PayPal — hors CSRF (signature verify-webhook-signature).
 * Route : POST /webhooks/paypal
 */
final class PaypalWebhookController
{
    public function __construct(
        private Request $request,
        private PaypalService $paypal,
        private OrderDao $orderDao,
        private PaypalPaymentSyncService $paypalSync,
        private EventDispatcher $dispatcher,
        private Logger $logger,
    ) {
    }

    public function handle(): JsonResponse
    {
        $payload = (string) file_get_contents('php://input');
        $headers = $this->paypalHeaders();

        try {
            $event = $this->paypal->parseWebhook($payload, $headers);
        } catch (PaypalException $e) {
            $this->logger->error('Webhook PayPal : vérification échouée', [
                'message' => $e->getMessage(),
            ]);

            return JsonResponse::make(['error' => 'Invalid signature'], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Webhook PayPal : erreur', [
                'message' => $e->getMessage(),
            ]);

            return JsonResponse::make(['error' => 'Bad request'], 400);
        }

        $type = (string) ($event['event_type'] ?? '');
        $this->logger->info('Webhook PayPal reçu', ['type' => $type]);

        match ($type) {
            'PAYMENT.CAPTURE.COMPLETED',
            'CHECKOUT.ORDER.APPROVED' => $this->onCaptureOrApproved($event),
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.DECLINED' => $this->onCaptureFailed($event),
            'PAYMENT.CAPTURE.REFUNDED' => $this->onRefunded($event),
            default => null,
        };

        return JsonResponse::make(['received' => true], 200);
    }

    /** @param array<string, mixed> $event */
    private function onCaptureOrApproved(array $event): void
    {
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];
        $order = $this->resolveOrder($resource);
        if ($order === null || $order->isPaid()) {
            return;
        }

        $this->paypalSync->captureAndConfirm($order);
    }

    /** @param array<string, mixed> $event */
    private function onCaptureFailed(array $event): void
    {
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];
        $order = $this->resolveOrder($resource);
        if ($order === null) {
            return;
        }

        $this->paypalSync->markFailed($order, (string) ($event['summary'] ?? 'capture failed'));
    }

    /** @param array<string, mixed> $event */
    private function onRefunded(array $event): void
    {
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];
        $order = $this->resolveOrder($resource);
        if ($order === null || $order->isRefunded()) {
            return;
        }

        $this->orderDao->update($order->id, [
            'status'      => 'refunded',
            'refunded_at' => date('Y-m-d H:i:s'),
        ]);
        $this->dispatcher->dispatch(new OrderBecameRefunded($order->id));
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function resolveOrder(array $resource): ?Order
    {
        $customId = (string) ($resource['custom_id'] ?? '');
        if ($customId === '' && isset($resource['purchase_units'][0]['custom_id'])) {
            $customId = (string) $resource['purchase_units'][0]['custom_id'];
        }

        if ($customId !== '' && ctype_digit($customId)) {
            /** @var Order|null $byId */
            $byId = $this->orderDao->findById((int) $customId);
            if ($byId !== null) {
                return $byId;
            }
        }

        $paypalOrderId = (string) ($resource['id'] ?? '');
        // Sur capture, l'id resource est le capture id ; supplementary_data / links peuvent pointer order
        if (isset($resource['supplementary_data']['related_ids']['order_id'])) {
            $paypalOrderId = (string) $resource['supplementary_data']['related_ids']['order_id'];
        }

        if ($paypalOrderId !== '') {
            $byPaypal = $this->orderDao->findByPaypalOrderId($paypalOrderId);
            if ($byPaypal !== null) {
                return $byPaypal;
            }
        }

        $captureId = (string) ($resource['id'] ?? '');
        if ($captureId !== '') {
            return $this->orderDao->findByPaypalCaptureId($captureId);
        }

        return null;
    }

    /** @return array<string, string> */
    private function paypalHeaders(): array
    {
        $out = [];
        foreach ([
            'PAYPAL-TRANSMISSION-ID',
            'PAYPAL-TRANSMISSION-TIME',
            'PAYPAL-TRANSMISSION-SIG',
            'PAYPAL-CERT-URL',
            'PAYPAL-AUTH-ALGO',
        ] as $name) {
            $key = 'HTTP_' . str_replace('-', '_', $name);
            if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
                $out[strtolower($name)] = $_SERVER[$key];
            }
        }

        // Fallback via Request headers si dispo
        foreach (['paypal-transmission-id', 'paypal-transmission-time', 'paypal-transmission-sig', 'paypal-cert-url', 'paypal-auth-algo'] as $h) {
            $v = $this->request->header($h);
            if (is_string($v) && $v !== '') {
                $out[$h] = $v;
            }
        }

        return $out;
    }
}
