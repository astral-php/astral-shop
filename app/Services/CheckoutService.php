<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Models\Order;
use App\Models\Product;
use AstralShipping\Dao\OrderShipmentDao;
use AstralShipping\Models\ShippingSelection;
use AstralShipping\Service\ShippingService;
use PDO;
use Throwable;

final class CheckoutService
{
    public function __construct(
        private PDO $pdo,
        private CartService $cart,
        private ProductDao $productDao,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private ShippingService $shippingService,
        private OrderShipmentDao $shipmentDao,
    ) {}

    /**
     * @param array{
     *   shipping_name: string,
     *   shipping_line1: string,
     *   shipping_postal: string,
     *   shipping_city: string,
     *   shipping_country: string
     * } $shipping
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function placeOrder(
        int $userId,
        array $shipping,
        string $paymentMethod,
        ShippingSelection $selection,
    ): array {
        $allowedMethods = [Order::PAYMENT_CARD, Order::PAYMENT_TRANSFER, Order::PAYMENT_CHECK];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            return ['ok' => false, 'error' => 'Moyen de paiement invalide.'];
        }

        $lines = $this->cart->lines();
        if ($lines === []) {
            return ['ok' => false, 'error' => 'Votre panier est vide.'];
        }

        $prepared = [];
        $subtotal = 0;

        foreach ($lines as $line) {
            $product = $line['product'];
            if (!$line['available'] || !$product instanceof Product || !$product->isActive()) {
                return ['ok' => false, 'error' => 'Un article du panier n’est plus disponible. Mettez à jour le panier.'];
            }

            $fresh = $this->productDao->findById($product->id);
            /** @var Product|null $fresh */
            if ($fresh === null || !$fresh->isActive()) {
                return ['ok' => false, 'error' => "Le produit « {$product->name} » n’est plus disponible."];
            }
            if ($fresh->stock < $line['qty']) {
                return ['ok' => false, 'error' => "Stock insuffisant pour « {$fresh->name} » (dispo : {$fresh->stock})."];
            }

            $unit = $fresh->price_cents;
            $qty  = $line['qty'];
            $lineTotal = $unit * $qty;
            $subtotal += $lineTotal;

            $prepared[] = [
                'product_id'       => $fresh->id,
                'product_name'     => $fresh->name,
                'unit_price_cents' => $unit,
                'quantity'         => $qty,
                'line_total_cents' => $lineTotal,
            ];
        }

        if ($subtotal < 1) {
            return ['ok' => false, 'error' => 'Montant de commande invalide.'];
        }

        $shippingCents = $selection->quote->priceCents;
        $total = $this->shippingService->total($subtotal, $selection);

        try {
            $this->pdo->beginTransaction();

            $orderId = $this->orderDao->insert([
                'user_id'            => $userId,
                'subtotal_cents'     => $subtotal,
                'shipping_cents'     => $shippingCents,
                'total_cents'        => $total,
                'currency'           => 'eur',
                'status'             => 'pending',
                'payment_method'     => $paymentMethod,
                'fulfillment_status' => Order::FULFILLMENT_AWAITING,
                'stripe_intent_id'   => null,
                'invoice_path'       => null,
                'shipping_name'      => $shipping['shipping_name'],
                'shipping_line1'     => $shipping['shipping_line1'],
                'shipping_postal'      => $shipping['shipping_postal'],
                'shipping_city'      => $shipping['shipping_city'],
                'shipping_country'   => $shipping['shipping_country'],
            ]);

            foreach ($prepared as $row) {
                $this->orderItemDao->insert([
                    'order_id'         => $orderId,
                    'product_id'       => $row['product_id'],
                    'product_name'     => $row['product_name'],
                    'unit_price_cents' => $row['unit_price_cents'],
                    'quantity'         => $row['quantity'],
                    'line_total_cents' => $row['line_total_cents'],
                ]);
            }

            $this->shipmentDao->record($orderId, $selection);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['ok' => false, 'error' => 'Impossible d’enregistrer la commande. Réessayez.'];
        }

        $this->cart->clear();

        $order = $this->orderDao->findById($orderId);
        /** @var Order|null $order */
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande créée mais introuvable.'];
        }

        return ['ok' => true, 'order' => $order];
    }

    /**
     * @return array{
     *   ok: true,
     *   shipping: array{
     *     shipping_name: string,
     *     shipping_line1: string,
     *     shipping_postal: string,
     *     shipping_city: string,
     *     shipping_country: string
     *   }
     * }|array{ok: false, error: string}
     */
    public function reopenUnpaidOrder(Order $order): array
    {
        if ($order->isPaid() || $order->isCancelled()) {
            return ['ok' => false, 'error' => 'Cette commande ne peut plus être rouverte.'];
        }

        $items = [];
        foreach ($this->orderItemDao->findByOrder($order->id) as $line) {
            if ($line->product_id !== null && (int) $line->product_id > 0) {
                $items[] = [
                    'product_id' => (int) $line->product_id,
                    'qty'        => (int) $line->quantity,
                ];
            }
        }

        $shipping = [
            'shipping_name'    => $order->shipping_name,
            'shipping_line1'   => $order->shipping_line1,
            'shipping_postal'    => $order->shipping_postal,
            'shipping_city'    => $order->shipping_city,
            'shipping_country' => $order->shipping_country,
        ];

        $this->orderDao->update($order->id, [
            'status'           => Order::STATUS_CANCELLED,
            'stripe_intent_id' => null,
        ]);

        $this->cart->replaceItems($items);

        return ['ok' => true, 'shipping' => $shipping];
    }
}
