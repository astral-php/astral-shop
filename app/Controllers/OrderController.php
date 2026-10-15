<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderFulfillmentService;
use App\Services\StripePaymentSyncService;
use AstralShipping\Dao\OrderShipmentDao;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Exception\NotFoundException;
use Core\Http\JsonResponse;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

/** Espace client : suivi des commandes (le paiement se fait dans le tunnel boutique). */
final class OrderController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private Auth $auth,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private OrderShipmentDao $shipmentDao,
        private InvoiceService $invoices,
        private StripePaymentSyncService $stripeSync,
        private OrderFulfillmentService $fulfillment,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $userId = (int) $this->auth->id();
        $confirming = (string) $this->request->query('confirming', '') === '1';

        return $this->render('orders/index', [
            'title'      => 'Mes commandes',
            'shop'       => $this->shop,
            'orders'     => $this->orderDao->findByUser($userId),
            'confirming' => $confirming,
        ]);
    }

    public function show(string $id): Response
    {
        $order = $this->findOwnedOrder((int) $id);
        if ($order === null || $order->isCancelled()) {
            throw new NotFoundException('Commande introuvable.');
        }

        $confirming = (string) $this->request->query('confirming', '') === '1'
            && !$order->isPaid();

        if ($confirming) {
            $order = $this->stripeSync->confirmIfSucceeded($order);
            $confirming = !$order->isPaid();
        }

        return $this->render('orders/show', [
            'title'      => 'Commande #' . $order->id,
            'shop'       => $this->shop,
            'order'      => $order,
            'items'      => $this->orderItemDao->findByOrder($order->id),
            'shipment'   => $this->shipmentDao->findByOrder($order->id),
            'confirming' => $confirming,
        ]);
    }

    /** POST /orders/:id/request-refund — demande client (9.1d). */
    public function requestRefund(string $id): Response
    {
        $orderId = (int) $id;
        $order = $this->findOwnedOrder($orderId);
        if ($order === null || $order->isCancelled()) {
            throw new NotFoundException('Commande introuvable.');
        }

        // Admin qui consulte une commande tierce ne dépose pas une « demande client ».
        if ($this->auth->is(Role::ADMIN) && (int) $order->user_id !== (int) $this->auth->id()) {
            $this->session->flash('error', 'Utilisez l’admin pour traiter un remboursement.');
            return $this->redirect('/admin/orders/' . $order->id);
        }

        $result = $this->fulfillment->requestRefund(
            $order->id,
            (int) $this->auth->id(),
            (string) $this->request->input('refund_reason', ''),
            [
                'iban'   => (string) $this->request->input('refund_iban', ''),
                'bic'    => (string) $this->request->input('refund_bic', ''),
                'holder' => (string) $this->request->input('refund_holder', ''),
            ],
        );

        if (($result['ok'] ?? false) !== true) {
            $this->session->flash('error', $result['error'] ?? 'Demande impossible.');
            return $this->redirect('/orders/' . $orderId);
        }

        $this->session->flash(
            'success',
            'Votre demande de remboursement a bien été enregistrée. Nous vous recontactons rapidement.',
        );

        return $this->redirect('/orders/' . $result['order']->id);
    }

    /** GET /orders/:id/invoice — téléchargement facture PDF. */
    public function invoice(string $id): Response
    {
        $order = $this->findOwnedOrder((int) $id);
        if ($order === null || $order->isCancelled()) {
            throw new NotFoundException('Commande introuvable.');
        }

        if (!$order->isPaid()) {
            throw new NotFoundException('Facture indisponible.');
        }

        if (!$order->hasInvoice()) {
            $this->invoices->generateForOrder($order);
            $order = $this->findOwnedOrder($order->id) ?? $order;
        }

        $path = $this->invoices->absolutePath($order);
        if ($path === null) {
            throw new NotFoundException('Facture introuvable.');
        }

        $filename = 'facture-' . $order->id . '.pdf';
        $body = (string) file_get_contents($path);

        return (new Response($body, 200))
            ->addHeader('Content-Type', 'application/pdf')
            ->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->addHeader('Content-Length', (string) strlen($body));
    }

    /** GET /orders/:id/status — polling après retour Stripe. */
    public function status(string $id): JsonResponse
    {
        $order = $this->findOwnedOrder((int) $id);
        if ($order === null) {
            return JsonResponse::make(['error' => 'not_found'], 404);
        }

        if (!$order->isPaid() && $order->payment_method === Order::PAYMENT_CARD) {
            $order = $this->stripeSync->confirmIfSucceeded($order);
        }

        return JsonResponse::make([
            'id'                 => $order->id,
            'status'             => $order->status,
            'label'              => $order->paymentStateLabel(),
            'paid'               => $order->isPaid(),
            'payment_label'      => $order->paymentStateLabel(),
            'payment_badge'      => $order->paymentStateBadgeClass(),
            'fulfillment_status' => $order->fulfillment_status,
            'fulfillment_label'  => $order->fulfillmentLabel(),
            'fulfillment_badge'  => $order->fulfillmentBadgeClass(),
            'has_invoice'        => $order->hasInvoice(),
        ]);
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
}
