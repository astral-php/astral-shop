<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Events\OrderBecamePaid;
use App\Models\Order;
use App\Services\InvoiceService;
use AstralPayment\Events\PaymentSucceeded;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Throwable;

/** Génère la facture PDF dès que la commande est payée (avant l’e-mail de confirmation). */
final class GenerateInvoiceOnPayment implements ListenerInterface
{
    public function __construct(
        private OrderDao $orderDao,
        private InvoiceService $invoices,
        private Logger $logger,
    ) {}

    public function handle(EventInterface $event): void
    {
        $orderId = match (true) {
            $event instanceof PaymentSucceeded => $event->orderId,
            $event instanceof OrderBecamePaid  => $event->orderId,
            default                            => 0,
        };

        if ($orderId < 1) {
            return;
        }

        try {
            $order = $this->orderDao->findById($orderId);
            /** @var Order|null $order */
            if ($order === null || !$order->isPaid()) {
                return;
            }

            $path = $this->invoices->generateForOrder($order);
            if ($path === null) {
                $this->logger->warning('GenerateInvoiceOnPayment: facture non générée', [
                    'order_id' => $orderId,
                ]);
                return;
            }

            $this->logger->info('Facture PDF générée', [
                'order_id' => $orderId,
                'path'     => $path,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('GenerateInvoiceOnPayment: échec', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
