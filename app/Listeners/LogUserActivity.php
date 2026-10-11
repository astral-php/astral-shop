<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;

/**
 * Journalise les activités utilisateur dans storage/logs/.
 *
 * Écoute : UserRegistered, UserLoggedIn
 *
 * Ce listener peut être attaché à plusieurs événements.
 * Il adapte son message de log selon le type d'événement reçu.
 */
final class LogUserActivity implements ListenerInterface
{
    public function __construct(private Logger $logger) {}

    public function handle(EventInterface $event): void
    {
        match (true) {
            $event instanceof UserRegistered => $this->onRegistered($event),
            $event instanceof UserLoggedIn   => $this->onLoggedIn($event),
            default                          => null,
        };
    }

    private function onRegistered(UserRegistered $event): void
    {
        $this->logger->info('Nouvelle inscription', [
            'user_id' => $event->user->id,
            'email'   => $event->user->email,
            'mode'    => $event->registrationMode,
        ]);
    }

    private function onLoggedIn(UserLoggedIn $event): void
    {
        $this->logger->info('Connexion utilisateur', [
            'user_id' => $event->user->id,
            'email'   => $event->user->email,
            'ip'      => $event->ip,
        ]);
    }
}
