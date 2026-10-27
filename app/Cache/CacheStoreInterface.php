<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Store cache lecture (file ou Redis).
 * Ne pas utiliser pour panier / checkout / stock critique.
 */
interface CacheStoreInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, int $ttl = 3600): void;

    public function remember(string $key, int $ttl, callable $callback): mixed;

    public function forget(string $key): void;

    /** @return int Nombre d’entrées touchées / approximatif */
    public function flush(): int;
}
