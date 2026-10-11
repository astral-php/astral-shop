<?php

declare(strict_types=1);

namespace App\Models;

final class Category
{
    public int     $id          = 0;
    public string  $name        = '';
    public string  $slug        = '';
    public ?string $description = null;
    public int     $sort_order  = 0;
    public int|string $active   = 1;
    public string  $created_at  = '';
    public string  $updated_at  = '';

    public function isActive(): bool
    {
        return (int) $this->active === 1;
    }

    public function isPersisted(): bool
    {
        return $this->id > 0;
    }
}
