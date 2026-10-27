<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Cache\CatalogReadCache;
use App\Cache\FileCacheStore;
use App\Dao\CategoryDao;
use App\Dao\ProductDao;
use Core\Cache;
use PDO;
use PHPUnit\Framework\TestCase;

final class CatalogReadCacheTest extends TestCase
{
    private string $cacheDir;
    private PDO $pdo;
    private CatalogReadCache $catalog;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/astral-shop-cache-' . uniqid('', true);
        mkdir($this->cacheDir, 0775, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                active INTEGER NOT NULL DEFAULT 1
            );
            CREATE TABLE products (
                id INTEGER PRIMARY KEY,
                category_id INTEGER NULL,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT NULL,
                price_cents INTEGER NOT NULL,
                currency TEXT NOT NULL DEFAULT 'EUR',
                stock INTEGER NOT NULL DEFAULT 0,
                weight_g INTEGER NOT NULL DEFAULT 0,
                sku TEXT NULL,
                image_path TEXT NULL,
                image_url TEXT NULL,
                active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT '',
                updated_at TEXT NOT NULL DEFAULT ''
            );
        SQL);
        $this->pdo->exec("INSERT INTO categories (id, name, slug, active) VALUES (1, 'Cat', 'cat', 1)");
        $this->pdo->exec("INSERT INTO products (id, category_id, name, slug, price_cents, stock, active) VALUES (1, 1, 'Tee', 'tee', 2000, 5, 1)");

        $store = new FileCacheStore(new Cache($this->cacheDir));
        $this->catalog = new CatalogReadCache(
            $store,
            new ProductDao($this->pdo),
            new CategoryDao($this->pdo),
            60,
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
    }

    public function testRememberAndInvalidate(): void
    {
        $first = $this->catalog->activeProducts();
        $this->assertCount(1, $first);

        $this->pdo->exec("INSERT INTO products (id, category_id, name, slug, price_cents, stock, active) VALUES (2, 1, 'Hat', 'hat', 1000, 2, 1)");
        $cached = $this->catalog->activeProducts();
        $this->assertCount(1, $cached, 'sans invalidation : encore 1 produit');

        $this->catalog->invalidate();
        $fresh = $this->catalog->activeProducts();
        $this->assertCount(2, $fresh);
    }
}
