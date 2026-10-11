<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Dao\UserDao;
use Core\Http\RedirectResponse;
use Core\Middleware\MiddlewareInterface;
use Core\Request;

/**
 * Redirige vers /install tant qu’aucun compte n’existe.
 */
final class RequireInstalledMiddleware implements MiddlewareInterface
{
    public function __construct(private UserDao $userDao) {}

    public function handle(Request $request, callable $next): mixed
    {
        if ($this->userDao->count() === 0) {
            return RedirectResponse::to('/install');
        }

        return $next();
    }
}
