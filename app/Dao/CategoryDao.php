<?php

declare(strict_types=1);

namespace App\Dao;

use App\Models\Category;
use Database\AbstractDao;

/**
 * @extends AbstractDao<Category>
 */
final class CategoryDao extends AbstractDao
{
    protected function getTable(): string
    {
        return 'categories';
    }

    protected function getModelClass(): string
    {
        return Category::class;
    }

    public function findBySlug(string $slug): ?Category
    {
        $rows = $this->query(
            "SELECT * FROM {$this->getTable()} WHERE slug = :slug LIMIT 1",
            [':slug' => $slug],
        );

        /** @var Category|null */
        return $rows[0] ?? null;
    }

    /** @return list<Category> */
    public function findActiveOrdered(): array
    {
        /** @var list<Category> */
        return $this->query(
            "SELECT * FROM {$this->getTable()}
             WHERE active = 1
             ORDER BY sort_order ASC, name ASC",
        );
    }

    /** @return list<Category> */
    public function findAllOrdered(): array
    {
        /** @var list<Category> */
        return $this->query(
            "SELECT * FROM {$this->getTable()}
             ORDER BY sort_order ASC, name ASC",
        );
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            return $this->findBySlug($slug) !== null;
        }

        $rows = $this->query(
            "SELECT id FROM {$this->getTable()} WHERE slug = :slug AND id != :id LIMIT 1",
            [':slug' => $slug, ':id' => $exceptId],
        );

        return $rows !== [];
    }
}
