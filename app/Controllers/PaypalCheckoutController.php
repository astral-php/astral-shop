<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Models\Order;
use App\Services\PaypalPaymentSyncService;
use AstralPaypal\Exception\PaypalException;
use AstralPaypal\PaypalService;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Http\Response;
use Core\Logger;
use Core\Request;
use Core\Session;
use Core\View;
use Throwable;

/**
 * Checkout PayPal redirect (Orders v2 CAPTURE).
 */
final class PaypalCheckoutController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private Auth $auth,
        private OrderDao $orderDao,
        private PaypalService $paypal,
        private PaypalPaymentSyncService $paypalSync,
        private Logger $logger,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    /** Crée l’order PayPal et redirige vers l’URL approve. */
    public function start(): Response
    {
        $orderId = (int) $this->request->query('order_id', 0);
        $order = $this->findAuthorizedOrder($orderId);

        if ($order === null) {
            $this->session->flash('error', 'Commande introuvable.');
            return $this->redirect('/orders');
        }

        if ($order->isPaid()) {
            $this->session->flash('success', 'Cette commande est déjà payée.');
            return $this->redirect('/orders/' . $order->id);
        }

        if ($order->payment_method !== Order::PAYMENT_PAYPAL) {
            return $this->redirect('/checkout/instructions/' . $order->id);
        }

        if ($order->isCancelled()) {
            $this->session->flash('error', 'Cette commande a été annulée.');
            return $this->redirect('/checkout/payment');
        }

        if (!$this->paypal->isConfigured()) {
            $this->session->flash('error', 'Paiement PayPal non configuré (PAYPAL_CLIENT_ID / SECRET).');
            return $this->redirect('/orders/' . $order->id);
        }

        if ($order->status === 'failed') {
            $this->orderDao->update($order->id, ['status' => 'pending']);
            $order->status = 'pending';
        }

        try {
            if ($order->paypal_order_id) {
                $remote = $this->paypal->showOrder($order->paypal_order_id);
                if ($remote->isCompleted()) {
                    $synced = $this->paypalSync->markPaid(
                        $order,
                        $remote->id,
                        $remote->captureId,
                        $order->total_cents,
                    );
                    $this->clearCheckoutSession();
                    $this->session->flash('success', 'Paiement PayPal confirmé. Merci !');
                    return $this->redirect('/orders/' . $synced->id);
                }
                if ($remote->isApproved()) {
                    $synced = $this->paypalSync->captureAndConfirm($order);
                    if ($synced->isPaid()) {
                        $this->clearCheckoutSession();
                        $this->session->flash('success', 'Paiement PayPal confirmé. Merci !');
                        return $this->redirect('/orders/' . $synced->id);
                    }
                }
                $approve = $remote->approveUrl();
                if ($approve !== null) {
                    return $this->redirect($approve);
                }
            }

            $baseUrl = $this->appBaseUrl();
            $created = $this->paypal->createOrder(
                amountCents: $order->total_cents,
                currency: strtoupper($order->currency ?: 'EUR'),
                shopOrderReference: (string) $order->id,
                context: [
                    'return_url' => $baseUrl . '/payment/paypal/return',
                    'cancel_url' => $baseUrl . '/payment/paypal/cancel?order_id=' . $order->id,
                    'brand_name' => (string) ($this->shop['store_name'] ?? 'Astral Shop'),
                ],
            );

            $this->orderDao->update($order->id, [
                'paypal_order_id' => $created->id,
            ]);

            $approve = $created->approveUrl();
            if ($approve === null) {
                throw new PaypalException('URL d’approbation PayPal manquante.');
            }

            return $this->redirect($approve);
        } catch (Throwable $e) {
            $this->logger->error('PayPal checkout start échoué', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
                'class'    => $e::class,
            ]);

            $flash = 'Impossible d’initialiser le paiement PayPal.';
            if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $flash .= ' (' . $e->getMessage() . ')';
            }
            $this->session->flash('error', $flash);

            return $this->redirect('/orders/' . $order->id);
        }
    }

    /** Return URL après approbation PayPal (`token` = paypal order id). */
    public function returnFromPaypal(): Response
    {
        $token = trim((string) $this->request->query('token', ''));
        $order = $token !== '' ? $this->orderDao->findByPaypalOrderId($token) : null;

        if ($order === null) {
            $this->session->flash('error', 'Retour PayPal : commande introuvable.');
            return $this->redirect('/orders');
        }

        $authorized = $this->findAuthorizedOrder($order->id);
        if ($authorized === null) {
            $this->session->flash('error', 'Commande inaccessible.');
            return $this->redirect('/orders');
        }

        $this->clearCheckoutSession();

        if ($authorized->isPaid()) {
            $this->session->flash('success', 'Paiement PayPal confirmé. Merci !');
            return $this->redirect('/orders/' . $authorized->id);
        }

        $synced = $this->paypalSync->captureAndConfirm($authorized);
        if ($synced->isPaid()) {
            $this->session->flash('success', 'Paiement PayPal confirmé. Merci !');
            return $this->redirect('/orders/' . $synced->id);
        }

        for ($i = 0; $i < 6; $i++) {
            usleep(400_000);
            $fresh = $this->orderDao->findById($authorized->id);
            if ($fresh !== null && $fresh->isPaid()) {
                $this->session->flash('success', 'Paiement PayPal confirmé. Merci !');
                return $this->redirect('/orders/' . $fresh->id);
            }
        }

        $this->session->flash('success', 'Paiement accepté — confirmation en cours…');
        return $this->redirect('/orders/' . $authorized->id . '?confirming=1');
    }

    public function cancel(): Response
    {
        $orderId = (int) $this->request->query('order_id', 0);
        if ($orderId < 1) {
            $orderId = (int) $this->session->get('checkout.pending_order_id', 0);
        }
        if ($orderId > 0) {
            return $this->redirect('/checkout/change-payment/' . $orderId);
        }

        $this->session->flash('error', 'Paiement PayPal annulé — choisissez un autre moyen ou réessayez.');
        return $this->redirect('/checkout/payment');
    }

    private function clearCheckoutSession(): void
    {
        $this->session->forget('checkout.pending_order_id');
        $this->session->forget('checkout.shipping');
        $this->session->forget('checkout.billing');
        $this->session->forget('checkout.payment_method');
        $this->session->forget('checkout.shipping_method');
        $this->session->forget('checkout.pickup_point');
    }

    private function appBaseUrl(): string
    {
        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? $_ENV['APP_BASE_URL'] ?? ''), '/');

        return $baseUrl !== '' ? $baseUrl : 'http://astral-shop.local';
    }

    private function findAuthorizedOrder(int $orderId): ?Order
    {
        if ($orderId < 1) {
            return null;
        }

        if ($this->auth->is(Role::ADMIN)) {
            /** @var Order|null */
            return $this->orderDao->findById($orderId);
        }

        return $this->orderDao->findForUser($orderId, (int) $this->auth->id());
    }
}
