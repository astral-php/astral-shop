<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Astral\Template\TemplateServiceProvider;
use AstralPayment\PaymentServiceProvider;
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
];
