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
 *
 * Core 2.1 Cache refuse les objets sérialisés → stockage en tableaux associatifs.
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

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->store->remember(
            $key,
            $this->ttl,
            fn (): array => array_map(
                $this->productToArray(...),
                $this->productDao->findActive(null, $limit),
            ),
        );

        return array_map($this->productFromArray(...), $rows);
    }

    /** @return list<Product> */
    public function activeProducts(?int $categoryId = null): array
    {
        $suffix = $categoryId === null ? 'all' : 'cat.' . $categoryId;
        $key = $this->key('products.' . $suffix);

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->store->remember(
            $key,
            $this->ttl,
            fn (): array => array_map(
                $this->productToArray(...),
                $this->productDao->findActive($categoryId),
            ),
        );

        return array_map($this->productFromArray(...), $rows);
    }

    /** @return list<Category> */
    public function activeCategories(): array
    {
        $key = $this->key('categories');

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->store->remember(
            $key,
            $this->ttl,
            fn (): array => array_map(
                $this->categoryToArray(...),
                $this->categoryDao->findActiveOrdered(),
            ),
        );

        return array_map($this->categoryFromArray(...), $rows);
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

    /** @return array<string, mixed> */
    private function productToArray(Product $product): array
    {
        return get_object_vars($product);
    }

    /** @param array<string, mixed> $row */
    private function productFromArray(array $row): Product
    {
        $product = new Product();
        foreach ($row as $key => $value) {
            if (property_exists($product, $key)) {
                $product->{$key} = $value;
            }
        }

        return $product;
    }

    /** @return array<string, mixed> */
    private function categoryToArray(Category $category): array
    {
        return get_object_vars($category);
    }

    /** @param array<string, mixed> $row */
    private function categoryFromArray(array $row): Category
    {
        $category = new Category();
        foreach ($row as $key => $value) {
            if (property_exists($category, $key)) {
                $category->{$key} = $value;
            }
        }

        return $category;
    }
}
