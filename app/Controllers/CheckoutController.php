<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use AstralShipping\Exception\InvalidSelectionException;
use AstralShipping\Models\Address;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingSelection;
use AstralShipping\Service\ShippingService;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

/**
 * Tunnel boutique : panier → adresse → livraison → moyen de paiement → engagement.
 * La commande n’est créée qu’à l’engagement (carte / virement / chèque).
 */
final class CheckoutController extends AbstractController
{
    private const SESSION_SHIPPING = 'checkout.shipping';
    private const SESSION_BILLING  = 'checkout.billing';
    private const SESSION_METHOD   = 'checkout.shipping_method';
    private const SESSION_PENDING  = 'checkout.pending_order_id';

    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private Auth $auth,
        private CartService $cart,
        private CheckoutService $checkout,
        private ShippingService $shippingService,
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

        $old = array_merge($this->defaultAddressOld(), $this->sessionShipping(), $this->sessionBilling());

        return $this->render('checkout/show', [
            'title'  => 'Checkout',
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

        $shipping = [
            'shipping_name'    => trim((string) $this->request->input('shipping_name', '')),
            'shipping_line1'   => trim((string) $this->request->input('shipping_line1', '')),
            'shipping_postal'    => trim((string) $this->request->input('shipping_postal', '')),
            'shipping_city'    => trim((string) $this->request->input('shipping_city', '')),
            'shipping_country' => strtoupper(trim((string) $this->request->input('shipping_country', 'FR'))),
        ];

        if ($billingSame) {
            $billing = [
                'billing_name'    => $shipping['shipping_name'],
                'billing_line1'   => $shipping['shipping_line1'],
                'billing_postal'    => $shipping['shipping_postal'],
                'billing_city'    => $shipping['shipping_city'],
                'billing_country' => $shipping['shipping_country'],
                'billing_same_as_shipping' => '1',
            ];
        } else {
            $billing = [
                'billing_name'    => trim((string) $this->request->input('billing_name', '')),
                'billing_line1'   => trim((string) $this->request->input('billing_line1', '')),
                'billing_postal'    => trim((string) $this->request->input('billing_postal', '')),
                'billing_city'    => trim((string) $this->request->input('billing_city', '')),
                'billing_country' => strtoupper(trim((string) $this->request->input('billing_country', 'FR'))),
                'billing_same_as_shipping' => '0',
            ];
        }

        $errors = $this->validateAddressBlock($shipping, 'shipping');
        if (!$billingSame) {
            $errors = array_merge($errors, $this->validateAddressBlock($billing, 'billing'));
        }

        $old = array_merge($shipping, $billing);

        if ($errors !== []) {
            return $this->render('checkout/show', [
                'title'  => 'Checkout',
                'shop'   => $this->shop,
                'lines'  => $this->cart->lines(),
                'total'  => $this->cart->totalCents(),
                'errors' => $errors,
                'old'    => $old,
            ]);
        }

        $this->session->set(self::SESSION_SHIPPING, $shipping);
        $this->session->set(self::SESSION_BILLING, [
            'billing_name'    => $billing['billing_name'],
            'billing_line1'   => $billing['billing_line1'],
            'billing_postal'    => $billing['billing_postal'],
            'billing_city'    => $billing['billing_city'],
            'billing_country' => $billing['billing_country'],
            'billing_same_as_shipping' => $billingSame ? '1' : '0',
        ]);
        $this->session->forget(self::SESSION_METHOD);

        if ($this->isCheckedFlag('save_to_profile', false)) {
            $this->userDao->updateAddresses((int) $this->auth->id(), [
                'shipping_name'            => $shipping['shipping_name'],
                'shipping_line1'           => $shipping['shipping_line1'],
                'shipping_postal'            => $shipping['shipping_postal'],
                'shipping_city'            => $shipping['shipping_city'],
                'shipping_country'         => $shipping['shipping_country'],
                'billing_same_as_shipping' => $billingSame ? 1 : 0,
                'billing_name'             => $billing['billing_name'],
                'billing_line1'            => $billing['billing_line1'],
                'billing_postal'             => $billing['billing_postal'],
                'billing_city'             => $billing['billing_city'],
                'billing_country'          => $billing['billing_country'],
            ]);
        }

        return $this->redirect('/checkout/shipping');
    }

    /** GET offres de livraison (forfait FR + retrait). */
    public function shipping(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $address = $this->sessionShipping();
        if ($address === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $context = $this->shippingContext($address);
        $quotes  = $this->shippingService->quotes($context);

        if ($quotes === []) {
            $this->session->flash('error', 'Aucune offre de livraison pour cette adresse.');
            return $this->redirect('/checkout');
        }

        $selected = (string) $this->session->get(self::SESSION_METHOD, $quotes[0]->code);

        return $this->render('checkout/shipping', [
            'title'    => 'Livraison',
            'shop'     => $this->shop,
            'lines'    => $this->cart->lines(),
            'subtotal' => $context->subtotalCents,
            'quotes'   => $quotes,
            'selected' => $selected,
            'errors'   => [],
        ]);
    }

    /** POST choix livraison → session → paiement. */
    public function confirmShipping(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $address = $this->sessionShipping();
        if ($address === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $code    = trim((string) $this->request->input('shipping_method', ''));
        $context = $this->shippingContext($address);

        try {
            $this->shippingService->select($context, $code);
        } catch (InvalidSelectionException $e) {
            return $this->render('checkout/shipping', [
                'title'    => 'Livraison',
                'shop'     => $this->shop,
                'lines'    => $this->cart->lines(),
                'subtotal' => $context->subtotalCents,
                'quotes'   => $this->shippingService->quotes($context),
                'selected' => $code,
                'errors'   => ['shipping_method' => [$e->getMessage()]],
            ]);
        }

        $this->session->set(self::SESSION_METHOD, $code);

        return $this->redirect('/checkout/payment');
    }

    /** GET choix Carte / Virement / Chèque (session uniquement). */
    public function payment(): Response
    {
        if ($this->cart->isEmpty()) {
            $reopened = $this->tryReopenPendingCardOrder();
            if (!$reopened && $this->cart->isEmpty()) {
                $this->session->flash('error', 'Votre panier est vide.');
                return $this->redirect('/panier');
            }
        }

        if ($this->sessionShipping() === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $selection = $this->resolveSelection();
        if ($selection === null) {
            $this->session->flash('error', 'Choisissez un mode de livraison.');
            return $this->redirect('/checkout/shipping');
        }

        $subtotal = $this->cart->totalCents();

        return $this->render('checkout/payment', [
            'title'    => 'Paiement',
            'shop'     => $this->shop,
            'lines'    => $this->cart->lines(),
            'subtotal' => $subtotal,
            'shipping' => $selection->quote->priceCents,
            'total'    => $this->shippingService->total($subtotal, $selection),
            'quote'    => $selection->quote,
            'methods'  => $this->enabledMethods(),
            'errors'   => [],
            'old'      => [
                'payment_method' => (string) $this->session->get('checkout.payment_method', Order::PAYMENT_CARD),
            ],
        ]);
    }

    /**
     * POST engagement : crée la commande en BD.
     * Carte → Stripe ; virement/chèque → instructions boutique.
     */
    public function confirmPayment(): Response
    {
        if ($this->cart->isEmpty()) {
            $this->session->flash('error', 'Votre panier est vide.');
            return $this->redirect('/panier');
        }

        $shipping = $this->sessionShipping();
        if ($shipping === []) {
            $this->session->flash('error', 'Indiquez d’abord l’adresse de livraison.');
            return $this->redirect('/checkout');
        }

        $selection = $this->resolveSelection();
        if ($selection === null) {
            $this->session->flash('error', 'Choisissez un mode de livraison.');
            return $this->redirect('/checkout/shipping');
        }

        $method  = trim((string) $this->request->input('payment_method', ''));
        $enabled = $this->enabledMethods();

        if (!in_array($method, $enabled, true)) {
            $subtotal = $this->cart->totalCents();

            return $this->render('checkout/payment', [
                'title'    => 'Paiement',
                'shop'     => $this->shop,
                'lines'    => $this->cart->lines(),
                'subtotal' => $subtotal,
                'shipping' => $selection->quote->priceCents,
                'total'    => $this->shippingService->total($subtotal, $selection),
                'quote'    => $selection->quote,
                'methods'  => $enabled,
                'errors'   => ['payment_method' => ['Choisissez un moyen de paiement.']],
                'old'      => ['payment_method' => $method],
            ]);
        }

        $this->cancelPendingCardOrderOnly();

        $this->session->set('checkout.payment_method', $method);

        $billing = $this->sessionBillingForOrder($shipping);
        $result = $this->checkout->placeOrder((int) $this->auth->id(), $shipping, $method, $selection, $billing);

        if ($result['ok'] !== true) {
            $this->session->flash('error', $result['error']);
            return $this->redirect('/checkout/payment');
        }

        $order = $result['order'];

        if ($method === Order::PAYMENT_CARD) {
            $this->session->set(self::SESSION_PENDING, $order->id);
            $this->session->flash('success', 'Finalisez le paiement par carte.');
            return $this->redirect('/payment/checkout?order_id=' . $order->id);
        }

        $this->clearCheckoutSession();
        $this->session->flash(
            'success',
            'Commande confirmée — suivez les instructions ci-dessous pour régler (paiement validé à réception).'
        );
        return $this->redirect('/checkout/instructions/' . $order->id);
    }

    /** Instructions virement / chèque (tunnel boutique). */
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

    /** Annule un pending et revient au choix du moyen (depuis Stripe cancel ou lien client). */
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

        $this->session->set(self::SESSION_SHIPPING, $result['shipping']);
        $this->session->set(self::SESSION_BILLING, array_merge($result['billing'], [
            'billing_same_as_shipping' => $this->addressesEqual($result['shipping'], $result['billing']) ? '1' : '0',
        ]));
        $this->session->forget(self::SESSION_METHOD);
        $this->session->forget(self::SESSION_PENDING);
        $this->session->flash('success', 'Choisissez à nouveau la livraison et le moyen de paiement.');
        return $this->redirect('/checkout/shipping');
    }

    private function tryReopenPendingCardOrder(): bool
    {
        $pendingId = (int) $this->session->get(self::SESSION_PENDING, 0);
        if ($pendingId < 1) {
            return false;
        }

        $order = $this->findOwnedOrder($pendingId);
        $this->session->forget(self::SESSION_PENDING);

        if ($order === null || !$order->isOpen() || $order->payment_method !== Order::PAYMENT_CARD) {
            return false;
        }

        $result = $this->checkout->reopenUnpaidOrder($order);
        if ($result['ok'] !== true) {
            return false;
        }

        $this->session->set(self::SESSION_SHIPPING, $result['shipping']);
        if (isset($result['billing']) && is_array($result['billing'])) {
            $this->session->set(self::SESSION_BILLING, array_merge($result['billing'], [
                'billing_same_as_shipping' => $this->addressesEqual($result['shipping'], $result['billing']) ? '1' : '0',
            ]));
        }
        // Mode livraison conservé si encore en session ; sinon l’étape shipping le redemandera.

        return true;
    }

    private function cancelPendingCardOrderOnly(): void
    {
        $pendingId = (int) $this->session->get(self::SESSION_PENDING, 0);
        $this->session->forget(self::SESSION_PENDING);
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

        if ($this->auth->is(Role::ADMIN)) {
            /** @var Order|null */
            return $this->orderDao->findById($orderId);
        }

        return $this->orderDao->findForUser($orderId, (int) $this->auth->id());
    }

    /** @return list<string> */
    private function enabledMethods(): array
    {
        $configured = $this->shop['payment']['methods'] ?? ['card', 'transfer', 'check'];
        if (!is_array($configured)) {
            return [Order::PAYMENT_CARD];
        }

        $allowed = [Order::PAYMENT_CARD, Order::PAYMENT_TRANSFER, Order::PAYMENT_CHECK];
        $out = [];
        foreach ($configured as $m) {
            $m = (string) $m;
            if (in_array($m, $allowed, true)) {
                $out[] = $m;
            }
        }

        return $out !== [] ? $out : [Order::PAYMENT_CARD];
    }

    /**
     * @return array{
     *   shipping_name?: string,
     *   shipping_line1?: string,
     *   shipping_postal?: string,
     *   shipping_city?: string,
     *   shipping_country?: string
     * }
     */
    private function sessionShipping(): array
    {
        $data = $this->session->get(self::SESSION_SHIPPING, []);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array{
     *   shipping_name?: string,
     *   shipping_line1?: string,
     *   shipping_postal?: string,
     *   shipping_city?: string,
     *   shipping_country?: string
     * } $address
     */
    private function shippingContext(array $address): ShippingContext
    {
        return new ShippingContext(
            subtotalCents: $this->cart->totalCents(),
            weightGrams: 0,
            destination: new Address(
                country: (string) ($address['shipping_country'] ?? 'FR'),
                postalCode: (string) ($address['shipping_postal'] ?? ''),
                city: (string) ($address['shipping_city'] ?? ''),
                line1: (string) ($address['shipping_line1'] ?? ''),
            ),
        );
    }

    private function resolveSelection(): ?ShippingSelection
    {
        $address = $this->sessionShipping();
        $code    = trim((string) $this->session->get(self::SESSION_METHOD, ''));
        if ($address === [] || $code === '') {
            return null;
        }

        try {
            return $this->shippingService->select($this->shippingContext($address), $code);
        } catch (InvalidSelectionException) {
            $this->session->forget(self::SESSION_METHOD);
            return null;
        }
    }

    private function clearCheckoutSession(): void
    {
        $this->session->forget(self::SESSION_SHIPPING);
        $this->session->forget(self::SESSION_BILLING);
        $this->session->forget(self::SESSION_METHOD);
        $this->session->forget(self::SESSION_PENDING);
        $this->session->forget('checkout.payment_method');
    }

    /**
     * @return array<string, string>
     */
    private function defaultAddressOld(): array
    {
        $user = $this->userDao->findById((int) $this->auth->id());
        /** @var User|null $user */
        if ($user === null) {
            return [
                'shipping_name'            => $this->auth->name(),
                'shipping_line1'           => '',
                'shipping_postal'            => '',
                'shipping_city'            => '',
                'shipping_country'         => 'FR',
                'billing_same_as_shipping' => '1',
                'billing_name'             => '',
                'billing_line1'            => '',
                'billing_postal'             => '',
                'billing_city'             => '',
                'billing_country'          => 'FR',
            ];
        }

        $shipping = $user->shippingAddressArray();
        $billing = $user->billingAddressArray();

        return array_merge($shipping, $billing, [
            'billing_same_as_shipping' => $user->billingSameAsShipping() ? '1' : '0',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionBilling(): array
    {
        $data = $this->session->get(self::SESSION_BILLING, []);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array{
     *   shipping_name: string,
     *   shipping_line1: string,
     *   shipping_postal: string,
     *   shipping_city: string,
     *   shipping_country: string
     * } $shipping
     * @return array{
     *   billing_name: string,
     *   billing_line1: string,
     *   billing_postal: string,
     *   billing_city: string,
     *   billing_country: string
     * }
     */
    private function sessionBillingForOrder(array $shipping): array
    {
        $billing = $this->sessionBilling();
        $same = ((string) ($billing['billing_same_as_shipping'] ?? '1')) === '1';

        if ($same || trim((string) ($billing['billing_line1'] ?? '')) === '') {
            return [
                'billing_name'    => $shipping['shipping_name'],
                'billing_line1'   => $shipping['shipping_line1'],
                'billing_postal'    => $shipping['shipping_postal'],
                'billing_city'    => $shipping['shipping_city'],
                'billing_country' => $shipping['shipping_country'],
            ];
        }

        return [
            'billing_name'    => (string) ($billing['billing_name'] ?? ''),
            'billing_line1'   => (string) ($billing['billing_line1'] ?? ''),
            'billing_postal'    => (string) ($billing['billing_postal'] ?? ''),
            'billing_city'    => (string) ($billing['billing_city'] ?? ''),
            'billing_country' => (string) ($billing['billing_country'] ?? 'FR'),
        ];
    }

    /**
     * @param array<string, string> $data
     * @return array<string, list<string>>
     */
    private function validateAddressBlock(array $data, string $prefix): array
    {
        $nameKey = $prefix . '_name';
        $lineKey = $prefix . '_line1';
        $postalKey = $prefix . '_postal';
        $cityKey = $prefix . '_city';
        $countryKey = $prefix . '_country';

        $v = $this->validate([
            $nameKey    => $data[$nameKey] ?? '',
            $lineKey    => $data[$lineKey] ?? '',
            $postalKey   => $data[$postalKey] ?? '',
            $cityKey    => $data[$cityKey] ?? '',
            $countryKey => $data[$countryKey] ?? '',
        ], [
            $nameKey    => 'required|min:2|max:150',
            $lineKey    => 'required|min:3|max:255',
            $postalKey   => 'required',
            $cityKey    => 'required|min:2|max:100',
            $countryKey => 'required|min:2|max:2',
        ]);

        $errors = $v->errors();
        $postal = (string) ($data[$postalKey] ?? '');
        $postalLen = mb_strlen($postal);
        if ($postal !== '' && ($postalLen < 3 || $postalLen > 20)) {
            $errors[$postalKey] = ['Le code postal doit contenir entre 3 et 20 caractères.'];
        }

        return $errors;
    }

    /**
     * @param array<string, string> $shipping
     * @param array<string, string> $billing
     */
    private function addressesEqual(array $shipping, array $billing): bool
    {
        return ($shipping['shipping_name'] ?? '') === ($billing['billing_name'] ?? '')
            && ($shipping['shipping_line1'] ?? '') === ($billing['billing_line1'] ?? '')
            && ($shipping['shipping_postal'] ?? '') === ($billing['billing_postal'] ?? '')
            && ($shipping['shipping_city'] ?? '') === ($billing['billing_city'] ?? '')
            && ($shipping['shipping_country'] ?? '') === ($billing['billing_country'] ?? '');
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
