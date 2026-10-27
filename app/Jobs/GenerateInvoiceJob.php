<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Dao\OrderDao;
use App\Models\Order;
use App\Services\InvoiceService;
use Astral\Worker\Job\JobInterface;
use Core\Logger;

/**
 * Génère la facture PDF (InvoiceService) — payload : ['order_id' => int].
 * Idempotent : hasInvoice() court-circuite.
 */
final class GenerateInvoiceJob implements JobInterface
{
    public const TRIES = 3;
    public const BACKOFF = 30;

    public function __construct(
        private OrderDao $orderDao,
        private InvoiceService $invoices,
        private Logger $logger,
    ) {}

    public function handle(array $payload): void
    {
        $orderId = (int) ($payload['order_id'] ?? 0);
        if ($orderId < 1) {
            return;
        }

        $order = $this->orderDao->findById($orderId);
        /** @var Order|null $order */
        if ($order === null || !$order->isPaid()) {
            return;
        }

        $path = $this->invoices->generateForOrder($order);
        if ($path === null) {
            $this->logger->warning('GenerateInvoiceJob: facture non générée', [
                'order_id' => $orderId,
            ]);

            return;
        }

        $this->logger->info('Facture PDF générée (job)', [
            'order_id' => $orderId,
            'path'     => $path,
        ]);
    }
}
