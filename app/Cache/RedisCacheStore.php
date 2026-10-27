<?php

declare(strict_types=1);

namespace App\Cache;

use Redis;
use RuntimeException;

/**
 * Cache Redis (ext-redis). Préfixe de clés pour isoler le shop.
 */
final class RedisCacheStore implements CacheStoreInterface
{
    public function __construct(
        private Redis $redis,
        private string $prefix = 'astral:cache:',
    ) {}

    public static function connectFromEnv(): self
    {
        if (!class_exists(Redis::class)) {
            throw new RuntimeException('CACHE_DRIVER=redis nécessite l’extension PHP redis (ext-redis).');
        }

        $redis = new Redis();
        $host = (string) ($_ENV['REDIS_HOST'] ?? '127.0.0.1');
        $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);
        $timeout = 1.5;

        if (!$redis->connect($host, $port, $timeout)) {
            throw new RuntimeException("Impossible de joindre Redis {$host}:{$port}.");
        }

        $password = (string) ($_ENV['REDIS_PASSWORD'] ?? '');
        if ($password !== '') {
            $redis->auth($password);
        }

        $db = (int) ($_ENV['REDIS_DB'] ?? 0);
        if ($db > 0) {
            $redis->select($db);
        }

        $prefix = (string) ($_ENV['CACHE_PREFIX'] ?? 'astral:cache:');

        return new self($redis, $prefix);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->redis->get($this->prefixed($key));
        if ($raw === false || !is_string($raw)) {
            return $default;
        }

        $data = unserialize($raw);
        if ($data === false) {
            return $default;
        }

        return $data;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $payload = serialize($value);
        $fullKey = $this->prefixed($key);
        if ($ttl > 0) {
            $this->redis->setex($fullKey, $ttl, $payload);
        } else {
            $this->redis->set($fullKey, $payload);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function forget(string $key): void
    {
        $this->redis->del($this->prefixed($key));
    }

    public function flush(): int
    {
        $pattern = $this->prefix . '*';
        $count = 0;
        $it = null;
        while (true) {
            $keys = $this->redis->scan($it, $pattern, 100);
            if ($keys === false) {
                break;
            }
            foreach ($keys as $key) {
                $this->redis->del($key);
                $count++;
            }
            if ($it === 0 || $it === null) {
                break;
            }
        }

        return $count;
    }

    private function prefixed(string $key): string
    {
        return $this->prefix . $key;
    }
}
