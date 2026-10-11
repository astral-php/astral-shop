<?php

declare(strict_types=1);

namespace App\Events;

use Core\Events\EventInterface;

/** Paiement confirmé hors Stripe (virement / chèque validé par l’admin). */
final class OrderBecamePaid implements EventInterface
{
    public function __construct(
        public int $orderId,
    ) {}
}
