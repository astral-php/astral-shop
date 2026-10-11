<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
$driver = $_ENV['DB_DRIVER'] ?? 'sqlite';

return [
    'driver'   => $driver,
    'database' => $driver === 'sqlite'
        ? (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__))
            . DIRECTORY_SEPARATOR . ltrim($_ENV['DB_DATABASE'] ?? 'database/starter.sqlite', '/\\')
        : ($_ENV['DB_DATABASE'] ?? 'astral_starter'),
    'host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
    'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => $_ENV['DB_CHARSET']  ?? 'utf8mb4',
];
