<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Core\Events\EventInterface;

/**
 * Événement déclenché après une connexion réussie.
 *
 * Dispatché par AuthController::login() et AuthController::verifyEmail()
 * après l'appel à Auth::login().
 *
 * Listeners typiques :
 *   - LogUserActivity : journalise la connexion (IP, date, navigateur)
 */
final class UserLoggedIn implements EventInterface
{
    public function __construct(
        public User   $user,
        public string $ip,
    ) {}
}
