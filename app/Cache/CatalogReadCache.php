<?php

declare(strict_types=1);

namespace App\Cache;

use App\Dao\CategoryDao;
use App\Dao\ProductDao;
use App\Models\Category;
use App\Models\Product;

/**
 * Cache lecture catalogue / home (listes « froides »).
 * Pas de fiche produit (stock affiché) ni panier / checkout.
 *
 * Invalidation : bump de version (clés anciennes expirent via TTL).
 */
final class CatalogReadCache
{
    private const VERSION_KEY = 'catalog.version';

    public function __construct(
        private CacheStoreInterface $store,
        private ProductDao $productDao,
        private CategoryDao $categoryDao,
        private int $ttl = 300,
    ) {}

    /** @return list<Product> */
    public function featured(int $limit = 6): array
    {
        $key = $this->key('featured.' . max(1, $limit));

        /** @var list<Product> */
        return $this->store->remember($key, $this->ttl, fn (): array => $this->productDao->findActive(null, $limit));
    }

    /** @return list<Product> */
    public function activeProducts(?int $categoryId = null): array
    {
        $suffix = $categoryId === null ? 'all' : 'cat.' . $categoryId;
        $key = $this->key('products.' . $suffix);

        /** @var list<Product> */
        return $this->store->remember(
            $key,
            $this->ttl,
            fn (): array => $this->productDao->findActive($categoryId),
        );
    }

    /** @return list<Category> */
    public function activeCategories(): array
    {
        $key = $this->key('categories');

        /** @var list<Category> */
        return $this->store->remember(
            $key,
            $this->ttl,
            fn (): array => $this->categoryDao->findActiveOrdered(),
        );
    }

    /** Invalide les lectures catalogue (admin produit / catégorie). */
    public function invalidate(): void
    {
        $version = (int) $this->store->get(self::VERSION_KEY, 0);
        $this->store->set(self::VERSION_KEY, $version + 1, 86400 * 30);
    }

    private function key(string $suffix): string
    {
        $version = (int) $this->store->get(self::VERSION_KEY, 0);

        return 'catalog.v' . $version . '.' . $suffix;
    }
}
