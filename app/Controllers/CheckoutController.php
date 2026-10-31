<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Models\Order;
use App\Services\CartService;
use App\Services\Checkout\CheckoutAddressService;
use App\Services\Checkout\CheckoutPaymentOptions;
use App\Services\Checkout\CheckoutSessionStore;
use App\Services\Checkout\CheckoutShippingResolver;
use App\Services\CheckoutService;
use AstralShipping\Exception\InvalidSelectionException;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

/**
 * Tunnel boutique HTTP : panier → adresse → livraison → paiement → engagement.
 * Métier : CheckoutService + services Checkout\* (adresses / session / shipping / options).
 */
final class CheckoutController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private Auth $auth,
        private CartService $cart,
        private CheckoutService $checkout,
        private CheckoutSessionStore $checkoutSession,
        private CheckoutAddressService $addresses,
        private CheckoutShippingResolver $shipping,
        private CheckoutPaymentOptions $paymentOptions,
        private OrderDao $orderDao,
        private UserDao $userDao,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function show(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $old = array_merge(
            $this->addresses->defaultFormValues((int) $this->auth->id(), $this->auth->name()),
            $this->checkoutSession->shipping(),
            $this->checkoutSession->billing(),
        );

        return $this->render('checkout/show', [
            'title'  => 'Identification',
            'shop'   => $this->shop,
            'lines'  => $this->cart->lines(),
            'total'  => $this->cart->totalCents(),
            'errors' => [],
            'old'    => $old,
        ]);
    }

    /** POST adresse → session → choix livraison. */
    public function place(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $billingSame = $this->isCheckedFlag('billing_same_as_shipping', true);
        /** @var array<string, mixed> $post */
        $post = $this->request->post();
        $parsed = $this->addresses->parseAndValidateFromInput($post, $billingSame);
        $shipping = $parsed['shipping'];
        $billing = $parsed['billing'];
        $errors = $parsed['errors'];
        $old = array_merge($shipping, $billing);

        if ($errors !== []) {
            return $this->render('checkout/show', [
                'title'  => 'Identification',
                'shop'   => $this->shop,
                'lines'  => $this->cart->lines(),
                'total'  => $this->cart->totalCents(),
                'errors' => $errors,
                'old'    => $old,
            ]);
        }

        $this->checkoutSession->setShipping($shipping);
        $this->checkoutSession->setBilling([
            'billing_name'             => $billing['billing_name'],
            'billing_line1'            => $billing['billing_line1'],
            'billing_postal'             => $billing['billing_postal'],
            'billing_city'             => $billing['billing_city'],
            'billing_country'          => $billing['billing_country'],
            'billing_same_as_shipping' => $billingSame ? '1' : '0',
        ]);
        $this->checkoutSession->forgetMethod();

        if ($this->isCheckedFlag('save_to_profile', false)) {
            $this->userDao->updateAddresses(
                (int) $this->auth->id(),
                $this->addresses->profilePayload($shipping, $billing, $billingSame),
            );
        }

        return $this->redirect('/checkout/shipping');
    }

    /** GET offres de livraison. */
    public function shipping(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $address = $this->checkoutSession->shipping();
        if ($address === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $context = $this->shipping->context($address);
        $quotes = $this->shipping->quotes($address);

        if ($quotes === []) {
            $this->session->flash('error', 'Aucune offre de livraison pour cette adresse.');
            return $this->redirect('/checkout');
        }

        $selected = $this->checkoutSession->methodCode() !== ''
            ? $this->checkoutSession->methodCode()
            : $quotes[0]->code;
        $pickup = $this->checkoutSession->pickup();

        return $this->render('checkout/shipping', [
            'title'              => 'Livraison',
            'shop'               => $this->shop,
            'lines'              => $this->cart->lines(),
            'subtotal'           => $context->subtotalCents,
            'quotes'             => $quotes,
            'selected'           => $selected,
            'pickup_point_id'    => $pickup['id'] ?? '',
            'pickup_point_label' => $pickup['label'] ?? '',
            'errors'             => [],
        ]);
    }

    /** POST choix livraison → session → paiement. */
    public function confirmShipping(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $address = $this->checkoutSession->shipping();
        if ($address === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $code = trim((string) $this->request->input('shipping_method', ''));
        $pickupId = trim((string) $this->request->input('pickup_point_id', ''));
        $pickupLabel = trim((string) $this->request->input('pickup_point_label', ''));
        $context = $this->shipping->context($address);

        try {
            $selection = $this->shipping->select(
                $address,
                $code,
                $pickupId !== '' ? $pickupId : null,
                $pickupLabel !== '' ? $pickupLabel : null,
            );
        } catch (InvalidSelectionException $e) {
            return $this->render('checkout/shipping', [
                'title'              => 'Livraison',
                'shop'               => $this->shop,
                'lines'              => $this->cart->lines(),
                'subtotal'           => $context->subtotalCents,
                'quotes'             => $this->shipping->quotes($address),
                'selected'           => $code,
                'pickup_point_id'    => $pickupId,
                'pickup_point_label' => $pickupLabel,
                'errors'             => ['shipping_method' => [$e->getMessage()]],
            ]);
        }

        $this->checkoutSession->setMethodCode($code);
        if ($selection->pickupPoint !== null) {
            $this->checkoutSession->setPickup([
                'id'    => $selection->pickupPoint->id,
                'label' => $selection->pickupPoint->label,
            ]);
        } else {
            $this->checkoutSession->setPickup(null);
        }

        return $this->redirect('/checkout/payment');
    }

    /** GET choix moyen de paiement. */
    public function payment(): Response
    {
        if ($this->cart->isEmpty()) {
            $reopened = $this->tryReopenPendingOnlineOrder();
            if (!$reopened && $this->cart->isEmpty()) {
                $this->session->flash('error', 'Votre panier est vide.');
                return $this->redirect('/panier');
            }
        }

        if ($this->checkoutSession->shipping() === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $selection = $this->shipping->resolveSelection();
        if ($selection === null) {
            $this->session->flash('error', 'Choisissez un mode de livraison.');
            return $this->redirect('/checkout/shipping');
        }

        $subtotal = $this->cart->totalCents();

        return $this->render('checkout/payment', [
            'title'              => 'Paiement',
            'shop'               => $this->shop,
            'lines'              => $this->cart->lines(),
            'subtotal'           => $subtotal,
            'shipping'           => $selection->quote->priceCents,
            'total'              => $this->shipping->total($subtotal, $selection),
            'quote'              => $selection->quote,
            'pickup_point_label' => $selection->pickupPoint?->label ?? '',
            'pickup_point_id'    => $selection->pickupPoint?->id ?? '',
            'methods'            => $this->paymentOptions->enabledMethods(),
            'errors'             => [],
            'old'                => [
                'payment_method' => $this->checkoutSession->paymentMethod() !== ''
                    ? $this->checkoutSession->paymentMethod()
                    : Order::PAYMENT_CARD,
            ],
        ]);
    }

    /**
     * POST engagement : crée la commande.
     * Carte / PayPal → contrôleurs paiement dédiés ; offline → instructions.
     */
    public function confirmPayment(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $shipping = $this->checkoutSession->shipping();
        if ($shipping === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $selection = $this->shipping->resolveSelection();
        if ($selection === null) {
            $this->session->flash('error', 'Choisissez un mode de livraison.');
            return $this->redirect('/checkout/shipping');
        }

        $method = trim((string) $this->request->input('payment_method', ''));
        $enabled = $this->paymentOptions->enabledMethods();
        $acceptCgv = $this->isCheckedFlag('accept_cgv', false);

        $paymentErrors = [];
        if (!in_array($method, $enabled, true)) {
            $paymentErrors['payment_method'] = ['Choisissez un moyen de paiement.'];
        }
        if (!$acceptCgv) {
            $paymentErrors['accept_cgv'] = ['Vous devez accepter les conditions générales de vente pour continuer.'];
        }

        if ($paymentErrors !== []) {
            $subtotal = $this->cart->totalCents();

            return $this->render('checkout/payment', [
                'title'    => 'Paiement',
                'shop'     => $this->shop,
                'lines'    => $this->cart->lines(),
                'subtotal' => $subtotal,
                'shipping' => $selection->quote->priceCents,
                'total'    => $this->shipping->total($subtotal, $selection),
                'quote'    => $selection->quote,
                'methods'  => $enabled,
                'errors'   => $paymentErrors,
                'old'      => [
                    'payment_method' => $method,
                    'accept_cgv'     => $acceptCgv ? '1' : '',
                ],
            ]);
        }

        $this->cancelPendingOnlineOrderOnly();
        $this->checkoutSession->setPaymentMethod($method);

        $billing = $this->addresses->billingForOrder($shipping, $this->checkoutSession->billing());
        $result = $this->checkout->placeOrder((int) $this->auth->id(), $shipping, $method, $selection, $billing);

        if ($result['ok'] !== true) {
            $this->session->flash('error', $result['error']);
            return $this->redirect('/checkout/payment');
        }

        $order = $result['order'];

        if ($method === Order::PAYMENT_CARD) {
            $this->checkoutSession->setPendingOrderId($order->id);
            $this->session->flash('success', 'Finalisez le paiement par carte.');
            return $this->redirect('/payment/checkout?order_id=' . $order->id);
        }

        if ($method === Order::PAYMENT_PAYPAL) {
            $this->checkoutSession->setPendingOrderId($order->id);
            $this->session->flash('success', 'Finalisez le paiement avec PayPal.');
            return $this->redirect('/payment/paypal?order_id=' . $order->id);
        }

        $this->checkoutSession->clear();
        $this->session->flash(
            'success',
            'Commande confirmée — suivez les instructions ci-dessous pour régler (paiement validé à réception).'
        );
        return $this->redirect('/checkout/instructions/' . $order->id);
    }

    public function instructions(string $id): Response
    {
        $order = $this->findOwnedOrder((int) $id);
        if ($order === null) {
            throw new NotFoundException('Commande introuvable.');
        }

        if ($order->isPaid()) {
            return $this->redirect('/orders/' . $order->id);
        }

        if ($order->payment_method === Order::PAYMENT_CARD) {
            return $this->redirect('/payment/checkout?order_id=' . $order->id);
        }

        if ($order->payment_method === Order::PAYMENT_PAYPAL) {
            return $this->redirect('/payment/paypal?order_id=' . $order->id);
        }

        if ($order->isCancelled()) {
            $this->session->flash('error', 'Cette commande a été annulée.');
            return $this->redirect('/boutique');
        }

        return $this->render('checkout/instructions', [
            'title' => 'Commande confirmée #' . $order->id,
            'shop'  => $this->shop,
            'order' => $order,
        ]);
    }

    public function changePayment(string $id): Response
    {
        $order = $this->findOwnedOrder((int) $id);
        if ($order === null) {
            throw new NotFoundException('Commande introuvable.');
        }

        if (!$order->isOpen()) {
            $this->session->flash('error', 'Cette commande ne peut plus être modifiée.');
            return $this->redirect('/orders/' . $order->id);
        }

        $result = $this->checkout->reopenUnpaidOrder($order);
        if ($result['ok'] !== true) {
            $this->session->flash('error', $result['error']);
            return $this->redirect('/orders/' . $order->id);
        }

        $this->checkoutSession->setShipping($result['shipping']);
        $this->checkoutSession->setBilling(array_merge($result['billing'], [
            'billing_same_as_shipping' => $this->addresses->addressesEqual($result['shipping'], $result['billing']) ? '1' : '0',
        ]));
        $this->checkoutSession->forgetMethod();
        $this->checkoutSession->forgetPending();
        $this->session->flash('success', 'Choisissez à nouveau la livraison et le moyen de paiement.');
        return $this->redirect('/checkout/shipping');
    }

    private function tryReopenPendingOnlineOrder(): bool
    {
        $pendingId = $this->checkoutSession->pendingOrderId();
        if ($pendingId < 1) {
            return false;
        }

        $order = $this->findOwnedOrder($pendingId);
        $this->checkoutSession->forgetPending();

        if (
            $order === null
            || !$order->isOpen()
            || !in_array($order->payment_method, [Order::PAYMENT_CARD, Order::PAYMENT_PAYPAL], true)
        ) {
            return false;
        }

        $result = $this->checkout->reopenUnpaidOrder($order);
        if ($result['ok'] !== true) {
            return false;
        }

        $this->checkoutSession->setShipping($result['shipping']);
        if (isset($result['billing']) && is_array($result['billing'])) {
            $this->checkoutSession->setBilling(array_merge($result['billing'], [
                'billing_same_as_shipping' => $this->addresses->addressesEqual($result['shipping'], $result['billing']) ? '1' : '0',
            ]));
        }

        return true;
    }

    private function cancelPendingOnlineOrderOnly(): void
    {
        $pendingId = $this->checkoutSession->pendingOrderId();
        $this->checkoutSession->forgetPending();
        if ($pendingId < 1) {
            return;
        }

        $order = $this->findOwnedOrder($pendingId);
        if ($order === null || !$order->isOpen()) {
            return;
        }

        $this->checkout->cancelOpenOrder($order);
    }

    private function findOwnedOrder(int $orderId): ?Order
    {
        if ($orderId < 1) {
            return null;
        }

        if ($this->auth->is(Role::Admin)) {
            /** @var Order|null */
            return $this->orderDao->findById($orderId);
        }

        return $this->orderDao->findForUser($orderId, (int) $this->auth->id());
    }

    private function isCheckedFlag(string $key, bool $default): bool
    {
        $raw = $this->request->input($key, $default ? '1' : '0');
        if (is_array($raw)) {
            $raw = end($raw);
        }

        return (string) $raw === '1' || (string) $raw === 'on' || $raw === true || $raw === 1;
    }
}
