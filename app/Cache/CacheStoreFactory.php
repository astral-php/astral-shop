<?php

declare(strict_types=1);

namespace App\Cache;

use Core\Cache;
use Core\Logger;
use Throwable;

final class CacheStoreFactory
{
    public static function make(Cache $fileCache, ?Logger $logger = null): CacheStoreInterface
    {
        $driver = strtolower((string) ($_ENV['CACHE_DRIVER'] ?? 'file'));

        if ($driver !== 'redis') {
            return new FileCacheStore($fileCache);
        }

        try {
            return RedisCacheStore::connectFromEnv();
        } catch (Throwable $e) {
            $logger?->warning('CACHE_DRIVER=redis indisponible — fallback file', [
                'error' => $e->getMessage(),
            ]);

            return new FileCacheStore($fileCache);
        }
    }
}
