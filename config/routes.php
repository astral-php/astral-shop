<?php

declare(strict_types=1);

use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\OrderController;
use App\Controllers\PaymentCheckoutController;
use App\Controllers\ProfileController;
use App\Controllers\UserController;
use App\Middleware\RequireInstalledMiddleware;
use AstralPayment\Controllers\WebhookController;
use AstralPayment\Middleware\StripeWebhookMiddleware;
use Core\Auth\Middleware\AdminMiddleware;
use Core\Auth\Middleware\AuthMiddleware;
use Core\Auth\Middleware\GuestMiddleware;
use Core\Middleware\CsrfMiddleware;
use Core\Router;

return function (Router $router): void {

    // Bootstrap — accessible tant qu’aucun compte n’existe
    $router->get('/install',  InstallController::class, 'show')->middleware(GuestMiddleware::class);
    $router->post('/install', InstallController::class, 'store')->middleware([GuestMiddleware::class, CsrfMiddleware::class]);

    // Webhook Stripe — signature HMAC, hors install gate
    $router->post('/webhooks/stripe', WebhookController::class, 'handle')
        ->middleware(StripeWebhookMiddleware::class);

    $router->group('', function (Router $router): void {

        $router->get('/', HomeController::class, 'index');

        $router->get('/boutique', CatalogController::class, 'index');
        $router->get('/boutique/categorie/:slug', CatalogController::class, 'category');
        $router->get('/produit/:slug', CatalogController::class, 'show');

        $router->get('/panier', CartController::class, 'show');
        $router->group('', function (Router $r): void {
            $r->post('/panier/ajouter', CartController::class, 'add');
            $r->post('/panier/maj',     CartController::class, 'update');
            $r->post('/panier/retirer', CartController::class, 'remove');
        }, [CsrfMiddleware::class]);

        $router->get('/login',     AuthController::class, 'loginForm')->middleware(GuestMiddleware::class);
        $router->post('/login',    AuthController::class, 'login')->middleware(GuestMiddleware::class);
        $router->post('/logout',   AuthController::class, 'logout');

        $router->get('/register',  AuthController::class, 'registerForm')->middleware(GuestMiddleware::class);
        $router->post('/register', AuthController::class, 'register')->middleware(GuestMiddleware::class);

        $router->get('/verify-pending', AuthController::class, 'verifyPending');
        $router->get('/verify-email',   AuthController::class, 'verifyEmail');

        $router->get('/forgot-password',  AuthController::class, 'forgotPasswordForm')->middleware(GuestMiddleware::class);
        $router->post('/forgot-password', AuthController::class, 'forgotPassword')->middleware(GuestMiddleware::class);
        $router->get('/reset-password',   AuthController::class, 'resetPasswordForm')->middleware(GuestMiddleware::class);
        $router->post('/reset-password',  AuthController::class, 'resetPassword')->middleware(GuestMiddleware::class);

        $router->group('', function (Router $r): void {
            $r->get('/profile',            ProfileController::class, 'editForm');
            $r->post('/profile',           ProfileController::class, 'update');
            $r->post('/profile/password',  ProfileController::class, 'updatePassword');
            $r->post('/profile/addresses', ProfileController::class, 'updateAddresses');

            $r->get('/checkout',                    CheckoutController::class, 'show');
            $r->post('/checkout',                   CheckoutController::class, 'place');
            $r->get('/checkout/shipping',           CheckoutController::class, 'shipping');
            $r->post('/checkout/shipping',          CheckoutController::class, 'confirmShipping');
            $r->get('/checkout/payment',            CheckoutController::class, 'payment');
            $r->post('/checkout/payment',           CheckoutController::class, 'confirmPayment');
            $r->get('/checkout/instructions/:id',  CheckoutController::class, 'instructions');
            $r->get('/checkout/change-payment/:id', CheckoutController::class, 'changePayment');

            $r->get('/orders',             OrderController::class, 'index');
            $r->get('/orders/:id/status',  OrderController::class, 'status');
            $r->get('/orders/:id/invoice', OrderController::class, 'invoice');
            $r->get('/orders/:id',         OrderController::class, 'show');

            $r->get('/payment/checkout', PaymentCheckoutController::class, 'checkout');
            $r->get('/payment/success',  PaymentCheckoutController::class, 'success');
            $r->get('/payment/cancel',   PaymentCheckoutController::class, 'cancel');
        }, [AuthMiddleware::class, CsrfMiddleware::class]);

        $router->group('', function (Router $r): void {
            $r->get('/users',     UserController::class, 'index');
            $r->get('/users/:id', UserController::class, 'show');
        }, [AuthMiddleware::class]);

        $router->group('', function (Router $r): void {
            $r->get('/users/create',      UserController::class, 'create');
            $r->post('/users',            UserController::class, 'store');
            $r->post('/users/:id/delete', UserController::class, 'destroy');
        }, [AdminMiddleware::class, CsrfMiddleware::class]);

        $router->group('/admin', function (Router $r): void {
            $r->get('/users',           AdminUserController::class, 'index');
            $r->get('/users/create',    AdminUserController::class, 'create');
            $r->post('/users',          AdminUserController::class, 'store');
            $r->post('/users/:id/role', AdminUserController::class, 'updateRole');

            $r->get('/orders',                    AdminOrderController::class, 'index');
            $r->get('/orders/:id',                AdminOrderController::class, 'show');
            $r->post('/orders/:id/mark-paid',     AdminOrderController::class, 'markPaid');
            $r->post('/orders/:id/mark-preparing', AdminOrderController::class, 'markPreparing');
            $r->post('/orders/:id/mark-shipped',  AdminOrderController::class, 'markShipped');
            $r->post('/orders/:id/delete',        AdminOrderController::class, 'destroy');

            $r->get('/categories',              AdminCategoryController::class, 'index');
            $r->get('/categories/create',       AdminCategoryController::class, 'create');
            $r->post('/categories',             AdminCategoryController::class, 'store');
            $r->get('/categories/:id/edit',     AdminCategoryController::class, 'edit');
            $r->post('/categories/:id',         AdminCategoryController::class, 'update');
            $r->post('/categories/:id/delete',  AdminCategoryController::class, 'destroy');

            $r->get('/products',              AdminProductController::class, 'index');
            $r->get('/products/create',       AdminProductController::class, 'create');
            $r->post('/products',             AdminProductController::class, 'store');
            $r->get('/products/:id/edit',     AdminProductController::class, 'edit');
            $r->post('/products/:id',         AdminProductController::class, 'update');
            $r->post('/products/:id/delete',  AdminProductController::class, 'destroy');
        }, [AdminMiddleware::class, CsrfMiddleware::class]);

    }, [RequireInstalledMiddleware::class]);
};
