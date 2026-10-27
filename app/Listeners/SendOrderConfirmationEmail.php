<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderBecamePaid;
use App\Jobs\SendOrderConfirmationJob;
use Astral\Worker\Queue\QueueInterface;
use AstralPayment\Events\PaymentSucceeded;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;

/**
 * Met en file l'e-mail de confirmation après paiement (Stripe / PayPal / offline).
 */
final class SendOrderConfirmationEmail implements ListenerInterface
{
    public function __construct(
        private QueueInterface $queue,
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

        $this->queue->push(SendOrderConfirmationJob::class, ['order_id' => $orderId]);
    }
}
