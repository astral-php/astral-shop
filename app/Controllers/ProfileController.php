<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\UserDao;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;
use Database\Exception\DatabaseException;

/**
 * Contrôleur du profil utilisateur connecté.
 *
 * Permet à tout utilisateur authentifié de :
 *   - consulter et modifier son nom et son e-mail
 *   - changer son mot de passe (en fournissant l'ancien)
 *
 * Routes :
 *   GET  /profile          → editForm
 *   POST /profile          → update
 *   POST /profile/password → updatePassword
 */
final class ProfileController extends AbstractController
{
    public function __construct(
        View            $view,
        private Request $request,
        private Auth    $auth,
        private UserDao $userDao,
        private Session $session,
    ) {
        parent::__construct($view);
    }

    // -------------------------------------------------------------------------
    // Formulaire de profil
    // -------------------------------------------------------------------------

    public function editForm(): Response
    {
        $user = $this->userDao->findById((int) $this->auth->id());

        return $this->render('profile/edit', [
            'title'  => 'Mon profil',
            'user'   => $user,
            'errors' => [],
            'old'    => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Mise à jour nom / email
    // -------------------------------------------------------------------------

    public function update(): Response
    {
        $post = $this->request->post();

        $v = $this->validate($post, [
            'name'  => 'required|min:2|max:100',
            'email' => 'required|email',
        ]);

        $user = $this->userDao->findById((int) $this->auth->id());

        if ($v->fails()) {
            return $this->render('profile/edit', [
                'title'  => 'Mon profil',
                'user'   => $user,
                'errors' => $v->errors(),
                'old'    => $post,
            ]);
        }

        try {
            $this->userDao->updateProfile(
                userId: (int) $this->auth->id(),
                name:   trim((string) ($post['name'] ?? '')),
                email:  trim((string) ($post['email'] ?? '')),
            );

            // Mettre à jour les données de session
            $updatedUser = $this->userDao->findById((int) $this->auth->id());
            if ($updatedUser !== null) {
                $this->auth->login($updatedUser);
            }

            $this->session->flash('success', 'Profil mis à jour avec succès.');
            return $this->redirect('/profile');

        } catch (DatabaseException $e) {
            return $this->render('profile/edit', [
                'title'  => 'Mon profil',
                'user'   => $user,
                'errors' => ['email' => [$e->getMessage()]],
                'old'    => $post,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Changement de mot de passe
    // -------------------------------------------------------------------------

    public function updatePassword(): Response
    {
        $post = $this->request->post();

        $v = $this->validate($post, [
            'current_password'  => 'required',
            'password'          => 'required|min:8|confirmed',
        ]);

        $user = $this->userDao->findById((int) $this->auth->id());

        if ($v->fails()) {
            return $this->render('profile/edit', [
                'title'          => 'Mon profil',
                'user'           => $user,
                'errors'         => $v->errors(),
                'old'            => [],
                'password_tab'   => true,
            ]);
        }

        // Vérifier l'ancien mot de passe
        $authenticated = $this->userDao->authenticate(
            $this->auth->email(),
            (string) ($post['current_password'] ?? ''),
        );

        if ($authenticated === null) {
            return $this->render('profile/edit', [
                'title'        => 'Mon profil',
                'user'         => $user,
                'errors'       => ['current_password' => ['Mot de passe actuel incorrect.']],
                'old'          => [],
                'password_tab' => true,
            ]);
        }

        $this->userDao->updatePassword(
            userId:      (int) $this->auth->id(),
            newPassword: (string) ($post['password'] ?? ''),
        );

        $this->session->flash('success', 'Mot de passe modifié avec succès.');
        return $this->redirect('/profile');
    }
}
