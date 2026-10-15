<?php

declare(strict_types=1);

namespace App\Events;

use Core\Events\EventInterface;

/** Admin a accepté une demande de remboursement client. */
final class RefundRequestAccepted implements EventInterface
{
    public function __construct(
        public int $orderId,
    ) {}
}
