<?php

declare(strict_types=1);

namespace App\Events;

use Core\Events\EventInterface;

/** Commande marquée comme expédiée (admin). */
final class OrderShipped implements EventInterface
{
    public function __construct(
        public int $orderId,
    ) {}
}
