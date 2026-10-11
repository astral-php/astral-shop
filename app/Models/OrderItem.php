<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;

final class OrderItem
{
    public int $id = 0;
    public int $order_id = 0;
    public ?int $product_id = null;
    public string $product_name = '';
    public int $unit_price_cents = 0;
    public int $quantity = 0;
    public int $line_total_cents = 0;

    public function unitLabel(string $symbol = '€'): string
    {
        return Money::formatCents($this->unit_price_cents, $symbol);
    }

    public function lineLabel(string $symbol = '€'): string
    {
        return Money::formatCents($this->line_total_cents, $symbol);
    }
}
