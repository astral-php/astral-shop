<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\UserDao;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;
use Database\Exception\DatabaseException;

/**
 * Contrôleur CRUD utilisateurs.
 *
 * Chaque action retourne un objet Response (HTML, JSON ou Redirect).
 * Le Router se charge d'appeler Response::send() après le dispatch.
 */
final class UserController extends AbstractController
{
    public function __construct(
        View            $view,
        private Request $request,
        private UserDao $userDao,
        private Session $session,
        private Auth    $auth,
    ) {
        parent::__construct($view);
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /** GET /users — liste paginée des utilisateurs. */
    public function index(): Response
    {
        $page   = (int) $this->request->query('page', 1);
        $result = $this->userDao->paginate(page: $page, perPage: 10, orderBy: 'name');

        return $this->render('user/index', [
            'title'   => 'Liste des utilisateurs',
            'users'   => $result['data'],
            'total'   => $result['total'],
            'pages'   => $result['pages'],
            'current' => $result['current'],
        ]);
    }

    /** GET /users/:id — affiche la fiche d'un utilisateur. */
    public function show(string $id): Response
    {
        $user = $this->userDao->findById((int) $id);

        if ($user === null) {
            throw new NotFoundException("Utilisateur #{$id} introuvable.");
        }

        return $this->render('user/show', [
            'title' => "Profil : {$user->name}",
            'user'  => $user,
        ]);
    }

    /** GET /users/create — formulaire de création. */
    public function create(): Response
    {
        return $this->render('user/create', [
            'title'  => 'Créer un utilisateur',
            'errors' => [],
            'old'    => [],
        ]);
    }

    /** POST /users — valide et enregistre un nouvel utilisateur. */
    public function store(): Response
    {
        $data = [
            'name'     => trim((string) $this->request->input('name')),
            'email'    => trim((string) $this->request->input('email')),
            'password' => (string) $this->request->input('password'),
        ];

        $v = $this->validate($data, [
            'name'     => 'required|min:2|max:100',
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($v->fails()) {
            return $this->render('user/create', [
                'title'  => 'Créer un utilisateur',
                'errors' => $v->errors(),
                'old'    => $data,
            ]);
        }

        try {
            $id = $this->userDao->createUser(
                name:          $data['name'],
                email:         $data['email'],
                plainPassword: $data['password'],
            );
        } catch (DatabaseException) {
            return $this->render('user/create', [
                'title'  => 'Créer un utilisateur',
                'errors' => ['email' => ['Cette adresse e-mail est déjà utilisée.']],
                'old'    => $data,
            ]);
        }

        $this->session->flash('success', "L'utilisateur {$data['name']} a été créé avec succès.");
        return $this->redirect("/users/{$id}");
    }

    /** POST /users/:id/delete — supprime un utilisateur. */
    public function destroy(string $id): Response
    {
        if ((int) $id === $this->auth->id()) {
            $this->session->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirect('/users');
        }

        $user    = $this->userDao->findById((int) $id);
        $deleted = $this->userDao->delete((int) $id);

        if ($deleted === 0) {
            throw new NotFoundException("Utilisateur #{$id} introuvable.");
        }

        $name = $user->name ?? "#{$id}";
        $this->session->flash('success', "L'utilisateur {$name} a été supprimé.");
        return $this->redirect('/users');
    }
}
