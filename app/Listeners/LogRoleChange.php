<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RoleChanged;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;

/**
 * Journalise chaque changement de rôle dans storage/logs/.
 *
 * Écoute : RoleChanged
 */
final class LogRoleChange implements ListenerInterface
{
    public function __construct(private Logger $logger) {}

    public function handle(EventInterface $event): void
    {
        if (!$event instanceof RoleChanged) {
            return;
        }

        $this->logger->info('Changement de rôle', [
            'user_id'    => $event->user->id,
            'user_email' => $event->user->email,
            'old_role'   => $event->oldRole,
            'new_role'   => $event->newRole,
            'changed_by' => $event->changedBy,
        ]);
    }
}
