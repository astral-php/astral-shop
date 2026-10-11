<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\UserDao;
use App\Events\UserRegistered;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Events\EventDispatcher;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;
use Database\Exception\DatabaseException;

/**
 * Bootstrap : création du premier compte administrateur.
 */
final class InstallController extends AbstractController
{
    public function __construct(
        View $view,
        private Request $request,
        private Auth $auth,
        private UserDao $userDao,
        private Session $session,
        private EventDispatcher $dispatcher,
        private string $registrationMode,
    ) {
        parent::__construct($view);
    }

    public function show(): Response
    {
        if ($this->userDao->count() > 0) {
            return $this->redirect('/');
        }

        return $this->render('install/show', [
            'title'  => 'Installation',
            'errors' => [],
            'old'    => [],
        ]);
    }

    public function store(): Response
    {
        if ($this->userDao->count() > 0) {
            return $this->redirect('/');
        }

        $post = $this->request->post();

        $v = $this->validate($post, [
            'name'     => 'required|min:2|max:100',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($v->fails()) {
            return $this->render('install/show', [
                'title'  => 'Installation',
                'errors' => $v->errors(),
                'old'    => $post,
            ]);
        }

        try {
            $userId = $this->userDao->createUser(
                name:          (string) ($post['name'] ?? ''),
                email:         (string) ($post['email'] ?? ''),
                plainPassword: (string) ($post['password'] ?? ''),
                role:          Role::ADMIN,
            );

            // Install = e-mail déjà maîtrisé ; éviter le blocage login en mode confirm.
            $this->userDao->markEmailVerified($userId);

            $user = $this->userDao->findById($userId);
            if ($user === null) {
                throw new \RuntimeException('Administrateur introuvable après création.');
            }

            $this->auth->login($user);
            $this->dispatcher->dispatch(new UserRegistered(
                user:             $user,
                registrationMode: $this->registrationMode,
            ));
            $this->session->flash('success', 'Boutique installée. Bienvenue, administrateur !');

            return $this->redirect('/admin/products');
        } catch (DatabaseException $e) {
            return $this->render('install/show', [
                'title'  => 'Installation',
                'errors' => ['email' => [$e->getMessage()]],
                'old'    => $post,
            ]);
        }
    }
}
