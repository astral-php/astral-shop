<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Dao\UserDao;
use App\Events\RoleChanged;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Events\EventDispatcher;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;
use Database\Exception\DatabaseException;

/**
 * Back-office comptes : gestionnaires (admin) vs clients (user).
 */
final class UserController extends AdminController
{
    public function __construct(
        View $view,
        private Request $request,
        private UserDao $userDao,
        private Session $session,
        private Auth $auth,
        private EventDispatcher $dispatcher,
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $filter = trim((string) $this->request->query('role', ''));
        if ($filter !== '' && !in_array($filter, [Role::ADMIN, Role::USER], true)) {
            $filter = '';
        }

        $users = $filter !== ''
            ? $this->userDao->findByRole($filter)
            : $this->userDao->findAll('id', 'DESC');

        $adminCount = count($this->userDao->findByRole(Role::ADMIN));
        $clientCount = count($this->userDao->findByRole(Role::USER));

        return $this->render('admin/users/index', [
            'title'       => 'Comptes',
            'users'       => $users,
            'roles'       => Role::all(),
            'adminCount'  => $adminCount,
            'clientCount' => $clientCount,
            'filter_role' => $filter,
        ]);
    }

    public function create(): Response
    {
        return $this->render('admin/users/form', [
            'title'  => 'Nouveau compte',
            'errors' => [],
            'old'    => ['role' => Role::ADMIN],
        ]);
    }

    public function store(): Response
    {
        $post = $this->request->post();

        $v = $this->validate($post, [
            'name'     => 'required|min:2|max:100',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:admin,user',
        ]);

        if ($v->fails()) {
            return $this->render('admin/users/form', [
                'title'  => 'Nouveau compte',
                'errors' => $v->errors(),
                'old'    => $post,
            ]);
        }

        $role = (string) ($post['role'] ?? Role::USER);
        if (!in_array($role, [Role::ADMIN, Role::USER], true)) {
            $role = Role::USER;
        }

        try {
            $this->userDao->createUser(
                name:          (string) ($post['name'] ?? ''),
                email:         (string) ($post['email'] ?? ''),
                plainPassword: (string) ($post['password'] ?? ''),
                role:          $role,
            );
        } catch (DatabaseException $e) {
            return $this->render('admin/users/form', [
                'title'  => 'Nouveau compte',
                'errors' => ['email' => [$e->getMessage()]],
                'old'    => $post,
            ]);
        }

        $label = $role === Role::ADMIN ? 'gestionnaire' : 'client';
        $this->session->flash('success', 'Compte ' . $label . ' créé.');

        return $this->redirect('/admin/users?role=' . rawurlencode($role));
    }

    public function updateRole(string $id): Response
    {
        $userId  = (int) $id;
        $newRole = trim((string) $this->request->input('role', ''));
        $back    = $this->filterRedirect();

        if ($userId === $this->auth->id()) {
            $this->session->flash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirect($back);
        }

        if (!Role::isValid($newRole) || $newRole === Role::GUEST) {
            $this->session->flash('error', "Rôle invalide : « {$newRole} ».");
            return $this->redirect($back);
        }

        $user = $this->userDao->findById($userId);
        if ($user === null) {
            throw new NotFoundException("Utilisateur #{$userId} introuvable.");
        }

        if ($user->role === Role::ADMIN && $newRole !== Role::ADMIN) {
            $adminCount = count($this->userDao->findByRole(Role::ADMIN));
            if ($adminCount <= 1) {
                $this->session->flash(
                    'error',
                    "Impossible de rétrograder {$user->name} : c'est le seul administrateur.",
                );
                return $this->redirect($back);
            }
        }

        $oldRole = $user->role;
        $this->userDao->promote($userId, $newRole);

        $this->dispatcher->dispatch(new RoleChanged(
            user:      $user,
            oldRole:   $oldRole,
            newRole:   $newRole,
            changedBy: (int) $this->auth->id(),
        ));

        $label = $newRole === Role::ADMIN ? 'Gestionnaire' : 'Client';
        $this->session->flash('success', "{$user->name} est maintenant « {$label} ».");

        return $this->redirect($back);
    }

    private function filterRedirect(): string
    {
        $role = trim((string) $this->request->input('filter_role', ''));
        if (in_array($role, [Role::ADMIN, Role::USER], true)) {
            return '/admin/users?role=' . rawurlencode($role);
        }

        return '/admin/users';
    }
}
