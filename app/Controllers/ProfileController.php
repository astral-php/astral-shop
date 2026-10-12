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

    // -------------------------------------------------------------------------
    // Adresses livraison / facturation
    // -------------------------------------------------------------------------

    public function updateAddresses(): Response
    {
        $user = $this->userDao->findById((int) $this->auth->id());
        $sameRaw = $this->request->input('billing_same_as_shipping', '1');
        if (is_array($sameRaw)) {
            $sameRaw = end($sameRaw);
        }
        $same = (string) $sameRaw === '1' || (string) $sameRaw === 'on';

        $shipping = [
            'shipping_name'    => trim((string) $this->request->input('shipping_name', '')),
            'shipping_line1'   => trim((string) $this->request->input('shipping_line1', '')),
            'shipping_postal'    => trim((string) $this->request->input('shipping_postal', '')),
            'shipping_city'    => trim((string) $this->request->input('shipping_city', '')),
            'shipping_country' => strtoupper(trim((string) $this->request->input('shipping_country', 'FR'))),
        ];

        if ($same) {
            $billing = [
                'billing_name'    => $shipping['shipping_name'],
                'billing_line1'   => $shipping['shipping_line1'],
                'billing_postal'    => $shipping['shipping_postal'],
                'billing_city'    => $shipping['shipping_city'],
                'billing_country' => $shipping['shipping_country'],
            ];
        } else {
            $billing = [
                'billing_name'    => trim((string) $this->request->input('billing_name', '')),
                'billing_line1'   => trim((string) $this->request->input('billing_line1', '')),
                'billing_postal'    => trim((string) $this->request->input('billing_postal', '')),
                'billing_city'    => trim((string) $this->request->input('billing_city', '')),
                'billing_country' => strtoupper(trim((string) $this->request->input('billing_country', 'FR'))),
            ];
        }

        $rules = [
            'shipping_name'    => 'required|min:2|max:150',
            'shipping_line1'   => 'required|min:3|max:255',
            'shipping_postal'    => 'required',
            'shipping_city'    => 'required|min:2|max:100',
            'shipping_country' => 'required|min:2|max:2',
        ];
        $payload = $shipping;

        if (!$same) {
            $rules = array_merge($rules, [
                'billing_name'    => 'required|min:2|max:150',
                'billing_line1'   => 'required|min:3|max:255',
                'billing_postal'    => 'required',
                'billing_city'    => 'required|min:2|max:100',
                'billing_country' => 'required|min:2|max:2',
            ]);
            $payload = array_merge($payload, $billing);
        }

        $v = $this->validate($payload, $rules);
        $errors = $v->errors();

        foreach (['shipping_postal', 'billing_postal'] as $postalKey) {
            if (!isset($payload[$postalKey])) {
                continue;
            }
            $len = mb_strlen((string) $payload[$postalKey]);
            if ($payload[$postalKey] !== '' && ($len < 3 || $len > 20)) {
                $errors[$postalKey] = ['Le code postal doit contenir entre 3 et 20 caractères.'];
            }
        }

        if ($errors !== []) {
            return $this->render('profile/edit', [
                'title'        => 'Mon profil',
                'user'         => $user,
                'errors'       => $errors,
                'old'          => array_merge($shipping, $billing, [
                    'billing_same_as_shipping' => $same ? '1' : '0',
                ]),
                'address_tab'  => true,
            ]);
        }

        $this->userDao->updateAddresses((int) $this->auth->id(), [
            'shipping_name'            => $shipping['shipping_name'],
            'shipping_line1'           => $shipping['shipping_line1'],
            'shipping_postal'            => $shipping['shipping_postal'],
            'shipping_city'            => $shipping['shipping_city'],
            'shipping_country'         => $shipping['shipping_country'],
            'billing_same_as_shipping' => $same ? 1 : 0,
            'billing_name'             => $billing['billing_name'],
            'billing_line1'            => $billing['billing_line1'],
            'billing_postal'             => $billing['billing_postal'],
            'billing_city'             => $billing['billing_city'],
            'billing_country'          => $billing['billing_country'],
        ]);

        $this->session->flash('success', 'Adresses enregistrées.');
        return $this->redirect('/profile#addresses');
    }
}
