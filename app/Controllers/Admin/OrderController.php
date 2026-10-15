<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Models\Order;
use App\Services\CancelledOrderPurgeService;
use App\Services\OrderFulfillmentService;
use AstralShipping\Dao\OrderShipmentDao;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

final class OrderController extends AdminController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private OrderShipmentDao $shipmentDao,
        private OrderFulfillmentService $fulfillment,
        private CancelledOrderPurgeService $purge,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $payment = trim((string) $this->request->query('payment', ''));
        // Alias legacy ?status= → paiement
        if ($payment === '') {
            $payment = trim((string) $this->request->query('status', ''));
        }
        $allowedPayment = ['pending', 'paid', 'failed', 'refunded', 'cancelled'];
        if ($payment !== '' && !in_array($payment, $allowedPayment, true)) {
            $payment = '';
        }

        $fulfillment = trim((string) $this->request->query('fulfillment', ''));
        $allowedFulfillment = [
            Order::FULFILLMENT_AWAITING,
            Order::FULFILLMENT_PREPARING,
            Order::FULFILLMENT_SHIPPED,
        ];
        if ($fulfillment !== '' && !in_array($fulfillment, $allowedFulfillment, true)) {
            $fulfillment = '';
        }

        $refundRequest = trim((string) $this->request->query('refund_request', ''));
        $allowedRefundRequest = [
            Order::REFUND_REQUEST_REQUESTED,
            Order::REFUND_REQUEST_ACCEPTED,
            Order::REFUND_REQUEST_REJECTED,
        ];
        if ($refundRequest !== '' && !in_array($refundRequest, $allowedRefundRequest, true)) {
            $refundRequest = '';
        }

        $paymentFilter = $payment !== '' ? $payment : null;
        $fulfillmentFilter = $fulfillment !== '' ? $fulfillment : null;
        $refundRequestFilter = $refundRequest !== '' ? $refundRequest : null;

        return $this->render('admin/orders/index', [
            'title'                   => 'Commandes',
            'shop'                    => $this->shop,
            'orders'                  => $this->orderDao->findAllWithUser(
                $paymentFilter,
                $fulfillmentFilter,
                $refundRequestFilter,
            ),
            'filter_payment'          => $payment,
            'filter_fulfillment'      => $fulfillment,
            'filter_refund_request'   => $refundRequest,
            'payment_counts'          => $this->orderDao->countGroupedByPayment($fulfillmentFilter),
            'fulfillment_counts'      => $this->orderDao->countGroupedByFulfillment($paymentFilter),
            'refund_request_pending'  => $this->orderDao->countRefundRequestsPending(),
        ]);
    }

    public function show(string $id): Response
    {
        $order = $this->orderDao->findByIdWithUser((int) $id);
        if ($order === null) {
            throw new NotFoundException('Commande introuvable.');
        }

        $shipment = $this->shipmentDao->findByOrder($order->id);
        if ($order->shipping_method_code === null && is_array($shipment)) {
            $order->shipping_method_code = (string) ($shipment['method_code'] ?? '');
        }

        return $this->render('admin/orders/show', [
            'title'    => 'Commande #' . $order->id,
            'shop'     => $this->shop,
            'order'    => $order,
            'items'    => $this->orderItemDao->findByOrder($order->id),
            'shipment' => $shipment,
        ]);
    }

    public function markPaid(string $id): Response
    {
        return $this->runTransition((int) $id, fn (int $oid) => $this->fulfillment->markPaid($oid), 'Commande marquée comme payée.');
    }

    public function markPreparing(string $id): Response
    {
        return $this->runTransition((int) $id, fn (int $oid) => $this->fulfillment->markPreparing($oid), 'Commande en préparation.');
    }

    public function markShipped(string $id): Response
    {
        $order = $this->orderDao->findByIdWithUser((int) $id);
        $pickup = $order instanceof Order && $order->isStorePickup();
        $msg = $pickup
            ? 'Commande marquée comme retirée en boutique — e-mail client envoyé.'
            : 'Commande marquée comme envoyée — e-mail client envoyé.';

        return $this->runTransition((int) $id, fn (int $oid) => $this->fulfillment->markShipped($oid), $msg);
    }

    public function refund(string $id): Response
    {
        return $this->runTransition(
            (int) $id,
            fn (int $oid) => $this->fulfillment->refundCard($oid),
            'Remboursement Stripe initié — commande marquée remboursée, stock rétabli, e-mail envoyé.',
        );
    }

    public function markRefunded(string $id): Response
    {
        $rib = [
            'iban'      => (string) $this->request->input('refund_iban', ''),
            'bic'       => (string) $this->request->input('refund_bic', ''),
            'holder'    => (string) $this->request->input('refund_holder', ''),
            'reference' => (string) $this->request->input('refund_reference', ''),
        ];

        return $this->runTransition(
            (int) $id,
            fn (int $oid) => $this->fulfillment->markRefundedOffline($oid, $rib),
            'Commande marquée remboursée — stock rétabli, e-mail envoyé.',
        );
    }

    public function acceptRefundRequest(string $id): Response
    {
        $order = $this->orderDao->findById((int) $id);
        $isCard = $order instanceof Order && $order->payment_method === Order::PAYMENT_CARD;
        $msg = $isCard
            ? 'Demande acceptée — remboursement Stripe effectué, stock rétabli, e-mail envoyé.'
            : 'Demande acceptée — effectuez le virement sortant puis marquez la commande remboursée.';

        return $this->runTransition(
            (int) $id,
            fn (int $oid) => $this->fulfillment->acceptRefundRequest($oid),
            $msg,
        );
    }

    public function rejectRefundRequest(string $id): Response
    {
        $reason = (string) $this->request->input('refund_reject_reason', '');

        return $this->runTransition(
            (int) $id,
            fn (int $oid) => $this->fulfillment->rejectRefundRequest($oid, $reason),
            'Demande de remboursement refusée — e-mail envoyé au client.',
        );
    }

    public function destroy(string $id): Response
    {
        $order = $this->orderDao->findById((int) $id);
        /** @var Order|null $order */
        if ($order === null) {
            throw new NotFoundException('Commande introuvable.');
        }

        $result = $this->purge->delete($order);
        if ($result['ok'] !== true) {
            $this->session->flash('error', $result['error']);
            return $this->redirect('/admin/orders/' . $order->id);
        }

        $this->session->flash('success', 'Commande annulée #' . $order->id . ' supprimée.');
        return $this->redirect('/admin/orders?payment=cancelled');
    }

    /**
     * @param callable(int): array{ok: true, order: Order}|array{ok: false, error: string} $action
     */
    private function runTransition(int $orderId, callable $action, string $successMessage): Response
    {
        $result = $action($orderId);

        if ($result['ok'] !== true) {
            $this->session->flash('error', $result['error']);
            return $this->redirect('/admin/orders/' . max(1, $orderId));
        }

        $this->session->flash('success', $successMessage);
        return $this->redirect('/admin/orders/' . $result['order']->id);
    }
}
