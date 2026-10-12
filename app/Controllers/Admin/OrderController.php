<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Models\Order;
use App\Services\CancelledOrderPurgeService;
use App\Services\OrderFulfillmentService;
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

        return $this->render('admin/orders/index', [
            'title'              => 'Commandes',
            'shop'               => $this->shop,
            'orders'             => $this->orderDao->findAllWithUser(
                $payment !== '' ? $payment : null,
                $fulfillment !== '' ? $fulfillment : null,
            ),
            'filter_payment'     => $payment,
            'filter_fulfillment' => $fulfillment,
        ]);
    }

    public function show(string $id): Response
    {
        $order = $this->orderDao->findByIdWithUser((int) $id);
        if ($order === null) {
            throw new NotFoundException('Commande introuvable.');
        }

        return $this->render('admin/orders/show', [
            'title' => 'Commande #' . $order->id,
            'shop'  => $this->shop,
            'order' => $order,
            'items' => $this->orderItemDao->findByOrder($order->id),
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
        return $this->runTransition((int) $id, fn (int $oid) => $this->fulfillment->markShipped($oid), 'Commande marquée comme envoyée — e-mail client envoyé.');
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
