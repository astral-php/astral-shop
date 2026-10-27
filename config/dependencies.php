<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Astral\Template\TemplateServiceProvider;
use Astral\Worker\WorkerServiceProvider;
use AstralPayment\PaymentServiceProvider;
use AstralPaypal\PaypalServiceProvider;
use AstralShipping\ShippingServiceProvider;
use Core\Providers\DatabaseServiceProvider;
use Core\Providers\FrameworkServiceProvider;

return [
    FrameworkServiceProvider::class,
    DatabaseServiceProvider::class,
    TemplateServiceProvider::class,
    ShippingServiceProvider::class,
    AppServiceProvider::class,
    PaymentServiceProvider::class,
    PaypalServiceProvider::class,
    WorkerServiceProvider::class, // après App (Mailer) + Database (PDO)
];
