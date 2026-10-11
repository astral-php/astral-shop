<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;

final class Product
{
    public int     $id           = 0;
    public ?int    $category_id  = null;
    public string  $name         = '';
    public string  $slug         = '';
    public ?string $description  = null;
    public int     $price_cents  = 0;
    public string  $currency     = 'EUR';
    public int     $stock        = 0;
    public ?string $sku          = null;
    public ?string $image_path   = null;
    public ?string $image_url    = null;
    public int|string $active    = 1;
    public string  $created_at   = '';
    public string  $updated_at   = '';

    /** Nom de catégorie joint (requêtes catalogue). */
    public ?string $category_name = null;

    public function isActive(): bool
    {
        return (int) $this->active === 1;
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    public function isPersisted(): bool
    {
        return $this->id > 0;
    }

    /** Prix TTC formaté. */
    public function priceLabel(string $symbol = '€'): string
    {
        return Money::formatCents($this->price_cents, $symbol);
    }

    /**
     * URL image publique, ou null → placeholder côté vue.
     * Priorité : image_url puis image_path.
     */
    public function imageSrc(): ?string
    {
        $url = trim((string) $this->image_url);
        if ($url !== '') {
            return $url;
        }

        $path = trim((string) $this->image_path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }
}
