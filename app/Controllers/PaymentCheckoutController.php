<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Models\Order;
use App\Services\StripePaymentSyncService;
use AstralPayment\PaymentService;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

/**
 * Checkout Stripe pour une commande shop (vérifie la propriété).
 */
final class PaymentCheckoutController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private Auth $auth,
        private OrderDao $orderDao,
        private PaymentService $paymentService,
        private StripePaymentSyncService $stripeSync,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function checkout(): Response
    {
        $orderId = (int) $this->request->query('order_id', 0);
        $order   = $this->findAuthorizedOrder($orderId);

        if ($order === null) {
            $this->session->flash('error', 'Commande introuvable.');
            return $this->redirect('/orders');
        }

        if ($order->isPaid()) {
            $this->session->flash('success', 'Cette commande est déjà payée.');
            return $this->redirect('/orders/' . $order->id);
        }

        if ($order->payment_method !== Order::PAYMENT_CARD) {
            return $this->redirect('/checkout/instructions/' . $order->id);
        }

        if ($order->isCancelled()) {
            $this->session->flash('error', 'Cette commande a été annulée.');
            return $this->redirect('/checkout/payment');
        }

        if ($order->status === 'failed') {
            // Permettre un nouvel essai
            $this->orderDao->update($order->id, ['status' => 'pending']);
            $order->status = 'pending';
        }

        $publicKey = (string) ($_ENV['STRIPE_PUBLIC_KEY'] ?? '');
        $secretKey = (string) ($_ENV['STRIPE_SECRET_KEY'] ?? '');

        if ($publicKey === '' || $secretKey === '') {
            $this->session->flash('error', 'Paiement Stripe non configuré (STRIPE_PUBLIC_KEY / STRIPE_SECRET_KEY).');
            return $this->redirect('/orders/' . $order->id);
        }

        try {
            if ($order->stripe_intent_id) {
                $intent       = $this->paymentService->retrievePaymentIntent($order->stripe_intent_id);
                $clientSecret = (string) $intent->client_secret;
            } else {
                $data = $this->paymentService->createPaymentIntent(
                    amountCents: $order->total_cents,
                    currency: strtolower($order->currency ?: 'eur'),
                    metadata: ['order_id' => $order->id],
                );
                $clientSecret = (string) $data['clientSecret'];
                $this->orderDao->update($order->id, [
                    'stripe_intent_id' => $data['intentId'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->session->flash('error', 'Impossible d’initialiser le paiement Stripe.');
            return $this->redirect('/orders/' . $order->id);
        }

        $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? $_ENV['APP_BASE_URL'] ?? ''), '/');
        if ($baseUrl === '') {
            $baseUrl = 'http://astral-shop.local';
        }

        return $this->render('payment/checkout', [
            'title'           => 'Paiement',
            'shop'            => $this->shop,
            'order'           => $order,
            'stripePublicKey' => $publicKey,
            'clientSecret'    => $clientSecret,
            'returnUrl'       => $baseUrl . '/payment/success',
            'amountFormatted' => PaymentService::toEuros($order->total_cents),
        ]);
    }

    public function success(): Response
    {
        $intentId = trim((string) $this->request->query('payment_intent', ''));
        $order    = $intentId !== '' ? $this->orderDao->findByStripeIntentId($intentId) : null;

        if ($order !== null) {
            $authorized = $this->findAuthorizedOrder($order->id);
            if ($authorized !== null) {
                $this->session->forget('checkout.pending_order_id');
                $this->session->forget('checkout.shipping');
                $this->session->forget('checkout.payment_method');

                if ($authorized->isPaid()) {
                    $this->session->flash('success', 'Paiement confirmé. Merci pour votre commande !');
                    return $this->redirect('/orders/' . $authorized->id);
                }

                // Webhook souvent à ~1 s : on attend brièvement, puis sync API Stripe si besoin.
                for ($i = 0; $i < 8; $i++) {
                    usleep(400_000); // 0,4 s
                    $fresh = $this->orderDao->findById($authorized->id);
                    /** @var Order|null $fresh */
                    if ($fresh !== null && $fresh->isPaid()) {
                        $this->session->flash('success', 'Paiement confirmé. Merci pour votre commande !');
                        return $this->redirect('/orders/' . $fresh->id);
                    }
                }

                $synced = $this->stripeSync->confirmIfSucceeded($authorized);
                if ($synced->isPaid()) {
                    $this->session->flash('success', 'Paiement confirmé. Merci pour votre commande !');
                    return $this->redirect('/orders/' . $synced->id);
                }

                $this->session->flash('success', 'Paiement accepté — confirmation en cours…');
                return $this->redirect('/orders/' . $authorized->id . '?confirming=1');
            }
        }

        $this->session->flash('success', 'Paiement accepté — confirmation en cours…');
        return $this->redirect('/orders?confirming=1');
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

        $this->session->flash('error', 'Paiement annulé — choisissez un autre moyen ou réessayez.');
        return $this->redirect('/checkout/payment');
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
