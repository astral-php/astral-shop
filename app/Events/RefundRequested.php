<?php

declare(strict_types=1);

namespace App\Events;

use Core\Events\EventInterface;

/** Client a soumis une demande de remboursement (9.1d). */
final class RefundRequested implements EventInterface
{
    public function __construct(
        public int $orderId,
    ) {}
}
