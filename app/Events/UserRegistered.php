<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Core\Events\EventInterface;

/**
 * Événement déclenché après la création d'un nouveau compte utilisateur.
 *
 * Dispatché par AuthController::register() après l'appel à UserDao::createUser().
 *
 * Listeners typiques :
 *   - SendWelcomeEmail   : envoie un e-mail de bienvenue (mode direct)
 *   - LogUserActivity    : journalise la nouvelle inscription
 */
final class UserRegistered implements EventInterface
{
    public function __construct(
        public User   $user,
        public string $registrationMode,
    ) {}
}
