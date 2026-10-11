<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\Product;
use Database\AbstractDao;

/**
 * @extends AbstractDao<Product>
 */
final class ProductDao extends AbstractDao
{
    protected function getTable(): string
    {
        return 'products';
    }

    protected function getModelClass(): string
    {
        return Product::class;
    }

    public function findBySlug(string $slug): ?Product
    {
        $rows = $this->query(
            "SELECT p.*, c.name AS category_name
             FROM {$this->getTable()} p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug
             LIMIT 1",
            [':slug' => $slug],
        );

        /** @var Product|null */
        return $rows[0] ?? null;
    }

    /** @return list<Product> */
    public function findActive(?int $categoryId = null, int $limit = 48): array
    {
        $sql = "SELECT p.*, c.name AS category_name
                FROM {$this->getTable()} p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1";
        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :cid';
            $params[':cid'] = $categoryId;
        }

        $sql .= ' ORDER BY p.name ASC LIMIT ' . max(1, min(200, $limit));

        /** @var list<Product> */
        return $this->query($sql, $params);
    }

    /** @return list<Product> */
    public function findAllWithCategory(): array
    {
        /** @var list<Product> */
        return $this->query(
            "SELECT p.*, c.name AS category_name
             FROM {$this->getTable()} p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.name ASC",
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $rows = $this->query(
                "SELECT id FROM {$this->getTable()} WHERE slug = :slug LIMIT 1",
                [':slug' => $slug],
            );

            return $rows !== [];
        }

        $rows = $this->query(
            "SELECT id FROM {$this->getTable()} WHERE slug = :slug AND id != :id LIMIT 1",
            [':slug' => $slug, ':id' => $exceptId],
        );

        return $rows !== [];
    }
}
