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
use App\Controllers\LegalController;
use App\Controllers\OrderController;
use App\Controllers\PaymentCheckoutController;
use App\Controllers\PaypalCheckoutController;
use App\Controllers\PaypalWebhookController;
use App\Controllers\ProfileController;
use App\Controllers\StripeWebhookController;
use App\Controllers\UserController;
use App\Support\ProductImageUploader;
use App\Dao\CategoryDao;
use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Dao\UserDao;
use App\Services\CancelledOrderPurgeService;
use App\Services\CartService;
use App\Services\Checkout\CheckoutAddressService;
use App\Services\Checkout\CheckoutPaymentOptions;
use App\Services\Checkout\CheckoutSessionStore;
use App\Services\Checkout\CheckoutShippingResolver;
use App\Services\CheckoutService;
use App\Services\OrderStockService;
use App\Services\PaypalPaymentSyncService;
use App\Services\StripePaymentSyncService;
use App\Events\OrderBecamePaid;
use App\Events\OrderBecameRefunded;
use App\Events\OrderShipped;
use App\Events\RefundRequestRejected;
use App\Events\RefundRequested;
use App\Events\RoleChanged;
use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Cache\CacheStoreFactory;
use App\Cache\CacheStoreInterface;
use App\Cache\CatalogReadCache;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Listeners\DecrementStockOnPaymentSucceeded;
use App\Listeners\GenerateInvoiceOnPayment;
use App\Listeners\LogRoleChange;
use App\Listeners\LogUserActivity;
use App\Listeners\NotifyAdminOnRefundRequested;
use App\Listeners\RestockOnRefund;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendOrderShippedEmail;
use App\Listeners\SendRefundConfirmationEmail;
use App\Listeners\SendRefundRequestReceivedEmail;
use App\Listeners\SendRefundRequestRejectedEmail;
use App\Listeners\SendWelcomeEmail;
use App\Services\InvoiceService;
use App\Services\OrderFulfillmentService;
use Astral\Worker\Queue\QueueInterface;
use AstralPayment\Dao\PaymentDao;
use AstralPayment\Events\PaymentRefunded;
use AstralPayment\Events\PaymentSucceeded;
use AstralPayment\PaymentService;
use AstralPaypal\PaypalService;
use AstralShipping\Dao\OrderShipmentDao;
use AstralShipping\Service\ShippingService;
use Core\Auth\Auth;
use Core\Cache;
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
        $dispatcher->listen(OrderBecameRefunded::class, RestockOnRefund::class);
        $dispatcher->listen(OrderBecameRefunded::class, SendRefundConfirmationEmail::class);
        $dispatcher->listen(PaymentRefunded::class, RestockOnRefund::class);
        $dispatcher->listen(PaymentRefunded::class, SendRefundConfirmationEmail::class);
        $dispatcher->listen(RefundRequested::class, NotifyAdminOnRefundRequested::class);
        $dispatcher->listen(RefundRequested::class, SendRefundRequestReceivedEmail::class);
        $dispatcher->listen(RefundRequestRejected::class, SendRefundRequestRejectedEmail::class);

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
            queue: $c->make(QueueInterface::class),
        ));

        $container->bind(SendOrderConfirmationEmail::class, fn (Container $c) => new SendOrderConfirmationEmail(
            queue: $c->make(QueueInterface::class),
        ));

        $container->bind(GenerateInvoiceJob::class, fn (Container $c) => new GenerateInvoiceJob(
            orderDao: $c->make(OrderDao::class),
            invoices: $c->make(InvoiceService::class),
            logger:   $c->make(Logger::class),
        ));

        $container->bind(SendOrderConfirmationJob::class, fn (Container $c) => new SendOrderConfirmationJob(
            mailer:       $c->make(Mailer::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            userDao:      $c->make(UserDao::class),
            logger:       $c->make(Logger::class),
            shop:         $shopConfig,
            appBaseUrl:   (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->bind(SendOrderShippedEmail::class, fn (Container $c) => new SendOrderShippedEmail(
            mailer:      $c->make(Mailer::class),
            orderDao:    $c->make(OrderDao::class),
            userDao:     $c->make(UserDao::class),
            shipmentDao: $c->make(OrderShipmentDao::class),
            logger:      $c->make(Logger::class),
            shop:        $shopConfig,
            appBaseUrl:  (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->bind(RestockOnRefund::class, fn (Container $c) => new RestockOnRefund(
            stock:    $c->make(OrderStockService::class),
            orderDao: $c->make(OrderDao::class),
            logger:   $c->make(Logger::class),
        ));

        $container->bind(SendRefundConfirmationEmail::class, fn (Container $c) => new SendRefundConfirmationEmail(
            mailer:     $c->make(Mailer::class),
            orderDao:   $c->make(OrderDao::class),
            userDao:    $c->make(UserDao::class),
            logger:     $c->make(Logger::class),
            shop:       $shopConfig,
            appBaseUrl: (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->bind(NotifyAdminOnRefundRequested::class, fn (Container $c) => new NotifyAdminOnRefundRequested(
            mailer:       $c->make(Mailer::class),
            orderDao:     $c->make(OrderDao::class),
            userDao:      $c->make(UserDao::class),
            logger:       $c->make(Logger::class),
            shop:         $shopConfig,
            appBaseUrl:   (string) ($appConfig['base_url'] ?? ''),
            notifyEmail:  (string) ($shopConfig['notify_email'] ?? ''),
        ));

        $container->bind(SendRefundRequestReceivedEmail::class, fn (Container $c) => new SendRefundRequestReceivedEmail(
            mailer:     $c->make(Mailer::class),
            orderDao:   $c->make(OrderDao::class),
            userDao:    $c->make(UserDao::class),
            logger:     $c->make(Logger::class),
            shop:       $shopConfig,
            appBaseUrl: (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->bind(SendRefundRequestRejectedEmail::class, fn (Container $c) => new SendRefundRequestRejectedEmail(
            mailer:     $c->make(Mailer::class),
            orderDao:   $c->make(OrderDao::class),
            userDao:    $c->make(UserDao::class),
            logger:     $c->make(Logger::class),
            shop:       $shopConfig,
            appBaseUrl: (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->singleton(OrderFulfillmentService::class, fn (Container $c) => new OrderFulfillmentService(
            orderDao:        $c->make(OrderDao::class),
            dispatcher:      $c->make(EventDispatcher::class),
            paymentService:  $c->make(PaymentService::class),
            paypalService:   $c->make(PaypalService::class),
            returnDays:      max(1, (int) (($shopConfig['legal']['return_days'] ?? 14))),
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

        $container->singleton(CheckoutSessionStore::class, fn (Container $c) => new CheckoutSessionStore(
            $c->make(Session::class),
        ));
        $container->singleton(CheckoutAddressService::class, fn (Container $c) => new CheckoutAddressService(
            $c->make(UserDao::class),
        ));
        $container->singleton(CheckoutShippingResolver::class, fn (Container $c) => new CheckoutShippingResolver(
            cart:            $c->make(CartService::class),
            shippingService: $c->make(ShippingService::class),
            sessionStore:    $c->make(CheckoutSessionStore::class),
        ));
        $container->singleton(CheckoutPaymentOptions::class, fn (Container $c) => new CheckoutPaymentOptions(
            shop:   $shopConfig,
            paypal: $c->make(PaypalService::class),
        ));

        $container->singleton(CacheStoreInterface::class, fn (Container $c) => CacheStoreFactory::make(
            $c->make(Cache::class),
            $c->make(Logger::class),
        ));

        $catalogTtl = max(30, (int) ($_ENV['CACHE_CATALOG_TTL'] ?? 300));
        $container->singleton(CatalogReadCache::class, fn (Container $c) => new CatalogReadCache(
            store:       $c->make(CacheStoreInterface::class),
            productDao:  $c->make(ProductDao::class),
            categoryDao: $c->make(CategoryDao::class),
            ttl:         $catalogTtl,
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
            view:         $c->make(View::class),
            userDao:      $c->make(UserDao::class),
            catalogCache: $c->make(CatalogReadCache::class),
            shop:         $shopConfig,
        ));

        $container->bind(LegalController::class, fn (Container $c) => new LegalController(
            view: $c->make(View::class),
            shop: $shopConfig,
        ));

        $container->bind(CatalogController::class, fn (Container $c) => new CatalogController(
            view:         $c->make(View::class),
            categoryDao:  $c->make(CategoryDao::class),
            productDao:   $c->make(ProductDao::class),
            catalogCache: $c->make(CatalogReadCache::class),
            shop:         $shopConfig,
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
            checkoutSession: $c->make(CheckoutSessionStore::class),
            addresses:       $c->make(CheckoutAddressService::class),
            shipping:        $c->make(CheckoutShippingResolver::class),
            paymentOptions:  $c->make(CheckoutPaymentOptions::class),
            orderDao:        $c->make(OrderDao::class),
            userDao:         $c->make(UserDao::class),
            shop:            $shopConfig,
        ));

        $container->singleton(StripePaymentSyncService::class, fn (Container $c) => new StripePaymentSyncService(
            orderDao:       $c->make(OrderDao::class),
            paymentService: $c->make(PaymentService::class),
            dispatcher:     $c->make(EventDispatcher::class),
            logger:         $c->make(Logger::class),
        ));

        $container->singleton(PaypalPaymentSyncService::class, fn (Container $c) => new PaypalPaymentSyncService(
            orderDao:   $c->make(OrderDao::class),
            paypal:     $c->make(PaypalService::class),
            dispatcher: $c->make(EventDispatcher::class),
            logger:     $c->make(Logger::class),
        ));

        $container->bind(OrderController::class, fn (Container $c) => new OrderController(
            view:         $c->make(View::class),
            request:      $c->make(Request::class),
            session:      $c->make(Session::class),
            auth:         $c->make(Auth::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            shipmentDao:  $c->make(OrderShipmentDao::class),
            invoices:     $c->make(InvoiceService::class),
            stripeSync:   $c->make(StripePaymentSyncService::class),
            paypalSync:   $c->make(PaypalPaymentSyncService::class),
            fulfillment:  $c->make(OrderFulfillmentService::class),
            shop:         $shopConfig,
        ));

        $container->bind(PaymentCheckoutController::class, fn (Container $c) => new PaymentCheckoutController(
            view:           $c->make(View::class),
            request:        $c->make(Request::class),
            session:        $c->make(Session::class),
            auth:           $c->make(Auth::class),
            orderDao:       $c->make(OrderDao::class),
            paymentService: $c->make(PaymentService::class),
            stripeSync:     $c->make(StripePaymentSyncService::class),
            shop:           $shopConfig,
        ));

        $container->bind(PaypalCheckoutController::class, fn (Container $c) => new PaypalCheckoutController(
            view:       $c->make(View::class),
            request:    $c->make(Request::class),
            session:    $c->make(Session::class),
            auth:       $c->make(Auth::class),
            orderDao:   $c->make(OrderDao::class),
            paypal:     $c->make(PaypalService::class),
            paypalSync: $c->make(PaypalPaymentSyncService::class),
            logger:     $c->make(Logger::class),
            shop:       $shopConfig,
        ));

        $container->bind(PaypalWebhookController::class, fn (Container $c) => new PaypalWebhookController(
            request:    $c->make(Request::class),
            paypal:     $c->make(PaypalService::class),
            orderDao:   $c->make(OrderDao::class),
            paypalSync: $c->make(PaypalPaymentSyncService::class),
            dispatcher: $c->make(EventDispatcher::class),
            logger:     $c->make(Logger::class),
        ));

        $container->bind(StripeWebhookController::class, fn (Container $c) => new StripeWebhookController(
            paymentService: $c->make(PaymentService::class),
            orderDao:       $c->make(OrderDao::class),
            paymentDao:     $c->make(PaymentDao::class),
            dispatcher:     $c->make(EventDispatcher::class),
            logger:         $c->make(Logger::class),
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
            view:             $c->make(View::class),
            request:          $c->make(Request::class),
            userDao:          $c->make(UserDao::class),
            session:          $c->make(Session::class),
            auth:             $c->make(Auth::class),
            dispatcher:       $c->make(EventDispatcher::class),
            mailer:           $c->make(Mailer::class),
            registrationMode: (string) ($appConfig['auth_registration'] ?? 'direct'),
            appBaseUrl:       (string) ($appConfig['base_url'] ?? ''),
        ));

        $container->bind(AdminCategoryController::class, fn (Container $c) => new AdminCategoryController(
            view:         $c->make(View::class),
            request:      $c->make(Request::class),
            categoryDao:  $c->make(CategoryDao::class),
            session:      $c->make(Session::class),
            catalogCache: $c->make(CatalogReadCache::class),
        ));

        $container->bind(AdminProductController::class, fn (Container $c) => new AdminProductController(
            view:         $c->make(View::class),
            request:      $c->make(Request::class),
            productDao:   $c->make(ProductDao::class),
            categoryDao:  $c->make(CategoryDao::class),
            session:      $c->make(Session::class),
            uploader:     $c->make(ProductImageUploader::class),
            catalogCache: $c->make(CatalogReadCache::class),
            shop:         $shopConfig,
        ));

        $container->bind(AdminOrderController::class, fn (Container $c) => new AdminOrderController(
            view:         $c->make(View::class),
            request:      $c->make(Request::class),
            session:      $c->make(Session::class),
            orderDao:     $c->make(OrderDao::class),
            orderItemDao: $c->make(OrderItemDao::class),
            shipmentDao:  $c->make(OrderShipmentDao::class),
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
