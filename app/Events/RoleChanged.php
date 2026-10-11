<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Core\Events\EventInterface;

/**
 * Événement déclenché après le changement de rôle d'un utilisateur.
 *
 * Dispatché par Admin\UserController::updateRole().
 *
 * Listeners typiques :
 *   - LogRoleChange : journalise l'action dans storage/logs/
 */
final class RoleChanged implements EventInterface
{
    public function __construct(
        public User   $user,
        public string $oldRole,
        public string $newRole,
        public int    $changedBy,
    ) {}
}
