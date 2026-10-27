<?php

declare(strict_types=1);

namespace App\Cache;

use Core\Cache;

/** Adaptateur autour de Core\Cache (storage/cache/). */
final class FileCacheStore implements CacheStoreInterface
{
    public function __construct(private Cache $cache) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->cache->set($key, $value, $ttl);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->cache->remember($key, $ttl, $callback);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }

    public function flush(): int
    {
        return $this->cache->flush();
    }
}
