<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Core\Mailer\Mailer;

/**
 * Envoie un e-mail de bienvenue après une inscription en mode direct.
 *
 * Écoute : UserRegistered
 *
 * En mode "confirm", l'e-mail de vérification est géré directement par
 * AuthController ; ce listener ne fait rien dans ce cas.
 */
final class SendWelcomeEmail implements ListenerInterface
{
    public function __construct(
        private Mailer $mailer,
        private Logger $logger,
    ) {}

    public function handle(EventInterface $event): void
    {
        if (!$event instanceof UserRegistered) {
            return;
        }

        // En mode confirmation, l'e-mail de vérification est déjà envoyé par le contrôleur
        if ($event->registrationMode === 'confirm') {
            return;
        }

        $user = $event->user;

        $html = <<<HTML
        <div style="font-family:sans-serif;max-width:520px;margin:auto;padding:32px">
            <h2 style="color:#4338ca">Bienvenue sur Astral MVC !</h2>
            <p>Bonjour <strong>{$user->name}</strong>,</p>
            <p>Votre compte a été créé avec succès. Vous pouvez dès maintenant vous connecter et explorer l'application.</p>
            <p style="color:#6b7280;font-size:13px">
                Si vous n'avez pas créé ce compte, contactez l'administrateur.
            </p>
        </div>
        HTML;

        try {
            $this->mailer->send($user->email, 'Bienvenue !', $html);
        } catch (\Throwable $e) {
            // Un échec d'envoi d'e-mail ne doit pas bloquer l'inscription
            $this->logger->warning('SendWelcomeEmail: échec envoi e-mail', [
                'user'  => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
