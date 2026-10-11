<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\UserDao;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Events\EventDispatcher;
use Core\Http\Response;
use Core\Mailer\Mailer;
use Core\Request;
use Core\Session;
use Core\View;
use Database\Exception\DatabaseException;

/**
 * Contrôleur d'authentification.
 *
 * Gère la connexion, la déconnexion, l'inscription (modes direct / confirm),
 * la confirmation d'e-mail et la réinitialisation du mot de passe.
 *
 * Mode d'inscription configuré via .env :
 *   AUTH_REGISTRATION=direct   → accès immédiat
 *   AUTH_REGISTRATION=confirm  → e-mail de confirmation requis
 */
final class AuthController extends AbstractController
{
    public function __construct(
        View                    $view,
        private Request         $request,
        private Auth            $auth,
        private UserDao         $userDao,
        private Session         $session,
        private Mailer          $mailer,
        private EventDispatcher $dispatcher,
        private string          $registrationMode,
        private string          $appBaseUrl,
    ) {
        parent::__construct($view);
    }

    // -------------------------------------------------------------------------
    // Connexion
    // -------------------------------------------------------------------------

    public function loginForm(): Response
    {
        $redirect = $this->captureRedirectFromQuery();

        return $this->render('auth/login', [
            'title'    => 'Connexion',
            'errors'   => [],
            'old'      => [],
            'redirect' => $redirect,
        ]);
    }

    public function login(): Response
    {
        $post = $this->request->post();
        $redirect = $this->safeRedirect((string) ($post['redirect'] ?? ''));

        $v = $this->validate($post, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            return $this->render('auth/login', [
                'title'    => 'Connexion',
                'errors'   => $v->errors(),
                'old'      => $post,
                'redirect' => $redirect,
            ]);
        }

        $email    = (string) ($post['email'] ?? '');
        $password = (string) ($post['password'] ?? '');

        $user = $this->userDao->authenticate($email, $password);

        if ($user === null) {
            return $this->render('auth/login', [
                'title'    => 'Connexion',
                'errors'   => ['email' => ['Identifiants invalides.']],
                'old'      => ['email' => $email],
                'redirect' => $redirect,
            ]);
        }

        // En mode confirmation : vérifier que l'e-mail a été confirmé
        if ($this->registrationMode === 'confirm' && !$user->isVerified()) {
            return $this->render('auth/login', [
                'title'    => 'Connexion',
                'errors'   => ['email' => ['Veuillez confirmer votre adresse e-mail avant de vous connecter.']],
                'old'      => ['email' => $email],
                'redirect' => $redirect,
            ]);
        }

        $this->auth->login($user);
        $this->dispatcher->dispatch(new UserLoggedIn(
            user: $user,
            ip:   (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ));
        $this->session->flash('success', "Bienvenue, {$user->name} !");

        return $this->redirect($this->resolvePostAuthRedirect($redirect));
    }

    // -------------------------------------------------------------------------
    // Déconnexion
    // -------------------------------------------------------------------------

    public function logout(): Response
    {
        $this->auth->logout();
        $this->session->flash('success', 'Vous avez été déconnecté.');

        return $this->redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Inscription
    // -------------------------------------------------------------------------

    public function registerForm(): Response
    {
        $redirect = $this->captureRedirectFromQuery();

        return $this->render('auth/register', [
            'title'    => 'Créer un compte',
            'errors'   => [],
            'old'      => [],
            'redirect' => $redirect,
        ]);
    }

    public function register(): Response
    {
        $post = $this->request->post();
        $redirect = $this->safeRedirect((string) ($post['redirect'] ?? ''));

        $v = $this->validate($post, [
            'name'     => 'required|min:2|max:100',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($v->fails()) {
            return $this->render('auth/register', [
                'title'    => 'Créer un compte',
                'errors'   => $v->errors(),
                'old'      => $post,
                'redirect' => $redirect,
            ]);
        }

        try {
            $userId = $this->userDao->createUser(
                name:          (string) ($post['name'] ?? ''),
                email:         (string) ($post['email'] ?? ''),
                plainPassword: (string) ($post['password'] ?? ''),
                role:          \Core\Auth\Role::USER,
            );

            $user = $this->userDao->findById($userId);

            if ($user === null) {
                throw new \RuntimeException("Utilisateur introuvable après création.");
            }

            // Mode confirmation : envoi de l'e-mail de vérification
            if ($this->registrationMode === 'confirm') {
                $token = bin2hex(random_bytes(32));
                $this->userDao->setVerificationToken($userId, $token);
                $this->sendVerificationEmail($user->email, $user->name, $token);

                return $this->redirect('/verify-pending');
            }

            // Mode direct : connexion immédiate
            $this->auth->login($user);
            $this->dispatcher->dispatch(new UserRegistered(
                user:             $user,
                registrationMode: $this->registrationMode,
            ));
            $this->session->flash('success', 'Compte créé avec succès. Bienvenue !');

            return $this->redirect($this->resolvePostAuthRedirect($redirect));

        } catch (DatabaseException $e) {
            return $this->render('auth/register', [
                'title'    => 'Créer un compte',
                'errors'   => ['email' => [$e->getMessage()]],
                'old'      => $post,
                'redirect' => $redirect,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Confirmation d'e-mail
    // -------------------------------------------------------------------------

    /** Page "Consultez vos e-mails" affichée après inscription en mode confirm. */
    public function verifyPending(): Response
    {
        return $this->render('auth/verify-pending', [
            'title' => 'Vérification en attente',
        ]);
    }

    /** GET /verify-email?token=xxx */
    public function verifyEmail(): Response
    {
        $token = (string) $this->request->query('token', '');

        if ($token === '') {
            $this->session->flash('error', 'Lien de vérification invalide.');
            return $this->redirect('/login');
        }

        $user = $this->userDao->verifyEmail($token);

        if ($user === null) {
            $this->session->flash('error', 'Ce lien est invalide ou a déjà été utilisé.');
            return $this->redirect('/login');
        }

        $this->auth->login($user);
        $this->session->flash('success', 'Adresse e-mail confirmée. Bienvenue, ' . $user->name . ' !');

        return $this->redirect('/');
    }

    // -------------------------------------------------------------------------
    // Mot de passe oublié
    // -------------------------------------------------------------------------

    public function forgotPasswordForm(): Response
    {
        return $this->render('auth/forgot-password', [
            'title'   => 'Mot de passe oublié',
            'errors'  => [],
            'old'     => [],
            'sent'    => false,
        ]);
    }

    public function forgotPassword(): Response
    {
        $post = $this->request->post();

        $v = $this->validate($post, ['email' => 'required|email']);

        if ($v->fails()) {
            return $this->render('auth/forgot-password', [
                'title'  => 'Mot de passe oublié',
                'errors' => $v->errors(),
                'old'    => $post,
                'sent'   => false,
            ]);
        }

        $email = (string) ($post['email'] ?? '');
        $token = $this->userDao->setPasswordResetToken($email);

        // Message neutre : ne pas révéler si l'adresse existe ou non
        if ($token !== null) {
            $this->sendPasswordResetEmail($email, $token);
        }

        return $this->render('auth/forgot-password', [
            'title'  => 'Mot de passe oublié',
            'errors' => [],
            'old'    => [],
            'sent'   => true,
        ]);
    }

    public function resetPasswordForm(): Response
    {
        $token = (string) $this->request->query('token', '');

        if ($token === '' || $this->userDao->findByPasswordResetToken($token) === null) {
            $this->session->flash('error', 'Ce lien est invalide ou a expiré.');
            return $this->redirect('/forgot-password');
        }

        return $this->render('auth/reset-password', [
            'title'  => 'Nouveau mot de passe',
            'token'  => $token,
            'errors' => [],
        ]);
    }

    public function resetPassword(): Response
    {
        $post  = $this->request->post();
        $token = (string) ($post['token'] ?? '');

        $v = $this->validate($post, [
            'password' => 'required|min:8|confirmed',
        ]);

        if ($v->fails()) {
            return $this->render('auth/reset-password', [
                'title'  => 'Nouveau mot de passe',
                'token'  => $token,
                'errors' => $v->errors(),
            ]);
        }

        $ok = $this->userDao->resetPassword($token, (string) ($post['password'] ?? ''));

        if (!$ok) {
            $this->session->flash('error', 'Ce lien est invalide ou a expiré.');
            return $this->redirect('/forgot-password');
        }

        $this->session->flash('success', 'Mot de passe modifié avec succès. Vous pouvez vous connecter.');

        return $this->redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Emails transactionnels
    // -------------------------------------------------------------------------

    private function sendVerificationEmail(string $to, string $name, string $token): void
    {
        $link = rtrim($this->appBaseUrl, '/') . '/verify-email?token=' . $token;

        $html = <<<HTML
        <div style="font-family:sans-serif;max-width:520px;margin:auto;padding:32px">
            <h2 style="color:#4338ca">Confirmez votre adresse e-mail</h2>
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Merci de vous être inscrit. Cliquez sur le bouton ci-dessous pour activer votre compte :</p>
            <p style="text-align:center;margin:32px 0">
                <a href="{$link}"
                   style="background:#4f46e5;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600">
                    Confirmer mon compte
                </a>
            </p>
            <p style="color:#6b7280;font-size:13px">Ce lien expire dans 24 heures.<br>
            Si vous n'avez pas créé de compte, ignorez cet e-mail.</p>
        </div>
        HTML;

        $this->mailer->send($to, 'Confirmez votre adresse e-mail', $html);
    }

    private function sendPasswordResetEmail(string $to, string $token): void
    {
        $link = rtrim($this->appBaseUrl, '/') . '/reset-password?token=' . $token;

        $html = <<<HTML
        <div style="font-family:sans-serif;max-width:520px;margin:auto;padding:32px">
            <h2 style="color:#4338ca">Réinitialisation du mot de passe</h2>
            <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
            <p style="text-align:center;margin:32px 0">
                <a href="{$link}"
                   style="background:#4f46e5;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600">
                    Choisir un nouveau mot de passe
                </a>
            </p>
            <p style="color:#6b7280;font-size:13px">Ce lien expire dans <strong>1 heure</strong>.<br>
            Si vous n'avez pas fait cette demande, ignorez cet e-mail.</p>
        </div>
        HTML;

        $this->mailer->send($to, 'Réinitialisation de votre mot de passe', $html);
    }

    // -------------------------------------------------------------------------
    // Redirection post-auth (panier → checkout, etc.)
    // -------------------------------------------------------------------------

    private function captureRedirectFromQuery(): string
    {
        $fromQuery = $this->safeRedirect((string) $this->request->query('redirect', ''));
        if ($fromQuery !== '/') {
            $this->session->set('auth_redirect', $fromQuery);

            return $fromQuery;
        }

        return $this->safeRedirect((string) $this->session->get('auth_redirect', ''));
    }

    private function resolvePostAuthRedirect(string $fromPost): string
    {
        $target = $fromPost !== '/'
            ? $fromPost
            : $this->safeRedirect((string) $this->session->get('auth_redirect', ''));

        $this->session->forget('auth_redirect');

        return $target;
    }

    /** Uniquement chemins relatifs locaux (anti open-redirect). */
    private function safeRedirect(string $target): string
    {
        $target = trim($target);
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return '/';
        }
        if (str_contains($target, '://') || str_contains($target, "\n") || str_contains($target, "\r")) {
            return '/';
        }

        return $target;
    }
}
