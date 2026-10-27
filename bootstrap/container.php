<?php

declare(strict_types=1);

/**
 * Construit le conteneur DI (CLI worker, scripts) sans HTTP ni session.
 *
 * Prérequis : BASE_PATH défini, autoload chargé, .env déjà safeLoad si besoin.
 */

use Core\Container;
use Core\ServiceProviderInterface;

/** @return Container */
function astral_shop_container(): Container
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

    /** @var array<string, mixed> $appConfig */
    $appConfig = require $basePath . '/config/app.php';
    /** @var array<string, mixed> $dbConfig */
    $dbConfig = require $basePath . '/config/database.php';

    date_default_timezone_set((string) ($appConfig['timezone'] ?? 'UTC'));

    $container = new Container();

    /** @var list<class-string<ServiceProviderInterface>> $providers */
    $providers = require $basePath . '/config/dependencies.php';

    foreach ($providers as $providerClass) {
        (new $providerClass())->register($container, $appConfig, $dbConfig);
    }

    return $container;
}
