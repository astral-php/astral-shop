<?php

declare(strict_types=1);

namespace App\Providers;

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
use App\Support\ProductImageUploader;
use App\Dao\CategoryDao;
use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Dao\UserDao;
use App\Services\CancelledOrderPurgeService;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderStockService;
use App\Events\OrderBecamePaid;
use App\Events\OrderShipped;
use App\Events\RoleChanged;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Listeners\DecrementStockOnPaymentSucceeded;
use App\Listeners\GenerateInvoiceOnPayment;
use App\Listeners\LogRoleChange;
use App\Listeners\LogUserActivity;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendOrderShippedEmail;
use App\Listeners\SendWelcomeEmail;
use App\Services\InvoiceService;
use App\Services\OrderFulfillmentService;
use AstralPayment\Events\PaymentSucceeded;
use AstralPayment\PaymentService;
use AstralShipping\Dao\OrderShipmentDao;
use AstralShipping\Service\ShippingService;
use Core\Auth\Auth;
use Core\Container;
use Core\Events\EventDispatcher;
use Core\Logger;
use Core\Mailer\Mailer;
use Core\Request;
use Core\ServiceProviderInterface;
use Core\Session;
use Core\View;

/**
 * Services applicatifs Astral Shop (auth + catalogue).
 */
final class AppServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $appConfig, array $dbConfig): void
    {
        /** @var array<string, mixed> $shopConfig */
        $shopConfig = require dirname(__DIR__, 2) . '/config/shop.php';

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container->make(EventDispatcher::class);

        $dispatcher->listen(UserRegistered::class, SendWelcomeEmail::class);
        $dispatcher->listen(UserRegistered::class, LogUserActivity::class);
        $dispatcher->listen(UserLoggedIn::class,   LogUserActivity::class);
        $dispatcher->listen(RoleChanged::class,    LogRoleChange::class);
        $dispatcher->listen(PaymentSucceeded::class, DecrementStockOnPaymentSucceeded::class);
        $dispatcher->listen(PaymentSucceeded::class, GenerateInvoiceOnPayment::class);
        $dispatcher->listen(PaymentSucceeded::class, SendOrderConfirmationEmail::class);
        $dispatcher->listen(OrderBecamePaid::class, DecrementStockOnPaymentSucceeded::class);
        $dispatcher->listen(OrderBecamePaid::class, GenerateInvoiceOnPayment::class);
        $dispatcher->listen(OrderBecamePaid::class, SendOrderConfirmationEmail::class);
        $dispatcher->listen(OrderShipped::class, SendOrderShippedEmail::class);

        $container->singleton(OrderStockService::class, fn (Container $c) => new OrderStockService(
            pdo:          $c->make(\PDO::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            productDao:   $c->make(ProductDao::class),
            logger:       $c->make(Logger::class),
        ));

        $container->singleton(CancelledOrderPurgeService::class, fn (Container $c) => new CancelledOrderPurgeService(
            pdo:          $c->make(\PDO::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            stock:        $c->make(OrderStockService::class),
        ));

        $container->bind(DecrementStockOnPaymentSucceeded::class, fn (Container $c) => new DecrementStockOnPaymentSucceeded(
            stock:  $c->make(OrderStockService::class),
            logger: $c->make(Logger::class),
        ));

        $container->singleton(InvoiceService::class, fn (Container $c) => new InvoiceService(
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            shop:         $shopConfig,
            storagePath:  dirname(__DIR__, 2) . '/storage/invoices',
        ));

        $container->bind(GenerateInvoiceOnPayment::class, fn (Container $c) => new GenerateInvoiceOnPayment(
            orderDao: $c->make(OrderDao::class),
            invoices: $c->make(InvoiceService::class),
            logger:   $c->make(Logger::class),
        ));

        $container->bind(SendOrderConfirmationEmail::class, fn (Container $c) => new SendOrderConfirmationEmail(
            mailer:       $c->make(Mailer::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            userDao:      $c->make(UserDao::class),
            logger:       $c->make(Logger::class),
            shop:         $shopConfig,
            appBaseUrl:   (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->bind(SendOrderShippedEmail::class, fn (Container $c) => new SendOrderShippedEmail(
            mailer:     $c->make(Mailer::class),
            orderDao:   $c->make(OrderDao::class),
            userDao:    $c->make(UserDao::class),
            logger:     $c->make(Logger::class),
            shop:       $shopConfig,
            appBaseUrl: (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->singleton(OrderFulfillmentService::class, fn (Container $c) => new OrderFulfillmentService(
            orderDao:   $c->make(OrderDao::class),
            dispatcher: $c->make(EventDispatcher::class),
        ));

        $container->singleton(UserDao::class, function (Container $c) use ($dbConfig): UserDao {
            $dao = new UserDao($c->make(\PDO::class));

            if (($dbConfig['driver'] ?? 'sqlite') === 'sqlite') {
                $dao->createTableIfNotExists();
            }

            return $dao;
        });

        $container->singleton(CategoryDao::class, fn (Container $c) => new CategoryDao($c->make(\PDO::class)));
        $container->singleton(ProductDao::class, fn (Container $c) => new ProductDao($c->make(\PDO::class)));

        $container->singleton(CartService::class, fn (Container $c) => new CartService(
            session:    $c->make(Session::class),
            productDao: $c->make(ProductDao::class),
        ));

        $container->singleton(OrderDao::class, fn (Container $c) => new OrderDao($c->make(\PDO::class)));
        $container->singleton(OrderItemDao::class, fn (Container $c) => new OrderItemDao($c->make(\PDO::class)));

        $paymentConfig = is_array($shopConfig['payment'] ?? null) ? $shopConfig['payment'] : [];
        $offlineDays = max(1, (int) ($paymentConfig['offline_payment_days'] ?? 7));

        $container->singleton(CheckoutService::class, fn (Container $c) => new CheckoutService(
            pdo:                 $c->make(\PDO::class),
            cart:                $c->make(CartService::class),
            productDao:          $c->make(ProductDao::class),
            orderDao:            $c->make(OrderDao::class),
            orderItemDao:        $c->make(OrderItemDao::class),
            shippingService:     $c->make(ShippingService::class),
            shipmentDao:         $c->make(OrderShipmentDao::class),
            stock:               $c->make(OrderStockService::class),
            offlinePaymentDays:  $offlineDays,
        ));

        $container->singleton(Mailer::class, function () use ($appConfig): Mailer {
            /** @var array<string, mixed> $mail */
            $mail = $appConfig['mail'] ?? [];

            return new Mailer(
                driver:     (string) ($mail['driver']     ?? 'mail'),
                host:       (string) ($mail['host']       ?? 'localhost'),
                port:       (int)    ($mail['port']       ?? 25),
                username:   (string) ($mail['username']   ?? ''),
                password:   (string) ($mail['password']   ?? ''),
                encryption: (string) ($mail['encryption'] ?? ''),
                from:       (string) ($mail['from']       ?? 'noreply@localhost'),
                fromName:   (string) ($mail['from_name']  ?? 'Astral Shop'),
            );
        });

        $container->make(View::class)->share('shop', $shopConfig);

        $container->singleton(ProductImageUploader::class, fn () => new ProductImageUploader());

        $container->bind(InstallController::class, fn (Container $c) => new InstallController(
            view:             $c->make(View::class),
            request:          $c->make(Request::class),
            auth:             $c->make(Auth::class),
            userDao:          $c->make(UserDao::class),
            session:          $c->make(Session::class),
            dispatcher:       $c->make(EventDispatcher::class),
            registrationMode: (string) ($appConfig['auth_registration'] ?? 'direct'),
        ));

        $container->bind(HomeController::class, fn (Container $c) => new HomeController(
            view:       $c->make(View::class),
            userDao:    $c->make(UserDao::class),
            productDao: $c->make(ProductDao::class),
            shop:       $shopConfig,
        ));

        $container->bind(CatalogController::class, fn (Container $c) => new CatalogController(
            view:        $c->make(View::class),
            categoryDao: $c->make(CategoryDao::class),
            productDao:  $c->make(ProductDao::class),
            shop:        $shopConfig,
        ));

        $container->bind(CartController::class, fn (Container $c) => new CartController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            session: $c->make(Session::class),
            cart:    $c->make(CartService::class),
            shop:    $shopConfig,
        ));

        $container->bind(CheckoutController::class, fn (Container $c) => new CheckoutController(
            view:            $c->make(View::class),
            request:         $c->make(Request::class),
            session:         $c->make(Session::class),
            auth:            $c->make(Auth::class),
            cart:            $c->make(CartService::class),
            checkout:        $c->make(CheckoutService::class),
            shippingService: $c->make(ShippingService::class),
            orderDao:        $c->make(OrderDao::class),
            shop:            $shopConfig,
        ));

        $container->bind(OrderController::class, fn (Container $c) => new OrderController(
            view:         $c->make(View::class),
            request:      $c->make(Request::class),
            auth:         $c->make(Auth::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            shipmentDao:  $c->make(OrderShipmentDao::class),
            invoices:     $c->make(InvoiceService::class),
            shop:         $shopConfig,
        ));

        $container->bind(PaymentCheckoutController::class, fn (Container $c) => new PaymentCheckoutController(
            view:           $c->make(View::class),
            request:        $c->make(Request::class),
            session:        $c->make(Session::class),
            auth:           $c->make(Auth::class),
            orderDao:       $c->make(OrderDao::class),
            paymentService: $c->make(PaymentService::class),
            shop:           $shopConfig,
        ));

        $container->bind(UserController::class, fn (Container $c) => new UserController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            userDao: $c->make(UserDao::class),
            session: $c->make(Session::class),
            auth:    $c->make(Auth::class),
        ));

        $container->bind(ProfileController::class, fn (Container $c) => new ProfileController(
            view:    $c->make(View::class),
            request: $c->make(Request::class),
            auth:    $c->make(Auth::class),
            userDao: $c->make(UserDao::class),
            session: $c->make(Session::class),
        ));

        $container->bind(AdminUserController::class, fn (Container $c) => new AdminUserController(
            view:       $c->make(View::class),
            request:    $c->make(Request::class),
            userDao:    $c->make(UserDao::class),
            session:    $c->make(Session::class),
            auth:       $c->make(Auth::class),
            dispatcher: $c->make(EventDispatcher::class),
        ));

        $container->bind(AdminCategoryController::class, fn (Container $c) => new AdminCategoryController(
            view:        $c->make(View::class),
            request:     $c->make(Request::class),
            categoryDao: $c->make(CategoryDao::class),
            session:     $c->make(Session::class),
        ));

        $container->bind(AdminProductController::class, fn (Container $c) => new AdminProductController(
            view:        $c->make(View::class),
            request:     $c->make(Request::class),
            productDao:  $c->make(ProductDao::class),
            categoryDao: $c->make(CategoryDao::class),
            session:     $c->make(Session::class),
            uploader:    $c->make(ProductImageUploader::class),
            shop:        $shopConfig,
        ));

        $container->bind(AdminOrderController::class, fn (Container $c) => new AdminOrderController(
            view:         $c->make(View::class),
            request:      $c->make(Request::class),
            session:      $c->make(Session::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            fulfillment:  $c->make(OrderFulfillmentService::class),
            purge:        $c->make(CancelledOrderPurgeService::class),
            shop:         $shopConfig,
        ));

        $container->bind(AuthController::class, fn (Container $c) => new AuthController(
            view:             $c->make(View::class),
            request:          $c->make(Request::class),
            auth:             $c->make(Auth::class),
            userDao:          $c->make(UserDao::class),
            session:          $c->make(Session::class),
            mailer:           $c->make(Mailer::class),
            dispatcher:       $c->make(EventDispatcher::class),
            registrationMode: (string) ($appConfig['auth_registration'] ?? 'direct'),
            appBaseUrl:       (string) ($appConfig['base_url'] ?? ''),
        ));
    }
}
