<?php

declare(strict_types=1);

namespace App\Events;

use Core\Events\EventInterface;

/** Commande passée en remboursée (admin CB Stripe ou offline). */
final class OrderBecameRefunded implements EventInterface
{
    public function __construct(
        public int $orderId,
    ) {}
}
