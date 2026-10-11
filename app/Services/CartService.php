<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\ProductDao;
use App\Models\Product;
use Core\Session;

/**
 * Panier MVP en session.
 * Stocke uniquement product_id + qty ; prix/nom rechargés depuis la DB.
 */
final class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private Session $session,
        private ProductDao $productDao,
    ) {}

    /** Nombre total d’unités dans le panier. */
    public function count(): int
    {
        $total = 0;
        foreach ($this->items() as $row) {
            $total += (int) ($row['qty'] ?? 0);
        }

        return $total;
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    /**
     * @return list<array{product_id: int, qty: int}>
     */
    public function items(): array
    {
        $cart = $this->session->get(self::SESSION_KEY, ['items' => []]);
        if (!is_array($cart) || !isset($cart['items']) || !is_array($cart['items'])) {
            return [];
        }

        $out = [];
        foreach ($cart['items'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pid = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $out[] = ['product_id' => $pid, 'qty' => $qty];
            }
        }

        return $out;
    }

    /**
     * Lignes hydratées pour l’affichage (prix TTC depuis la DB).
     *
     * @return list<array{
     *   product_id: int,
     *   qty: int,
     *   product: ?Product,
     *   unit_price_cents: int,
     *   line_total_cents: int,
     *   available: bool,
     *   message: ?string
     * }>
     */
    public function lines(): array
    {
        $lines = [];

        foreach ($this->items() as $row) {
            $product = $this->productDao->findById($row['product_id']);
            /** @var Product|null $product */

            if ($product === null) {
                $lines[] = [
                    'product_id'       => $row['product_id'],
                    'qty'              => $row['qty'],
                    'product'          => null,
                    'unit_price_cents' => 0,
                    'line_total_cents' => 0,
                    'available'        => false,
                    'message'          => 'Produit indisponible (supprimé).',
                ];
                continue;
            }

            $ok      = $product->isActive();
            $message = $ok ? null : 'Produit inactif.';
            if ($ok && $product->stock < $row['qty']) {
                $message = 'Stock insuffisant (dispo : ' . $product->stock . ').';
                $ok      = $product->stock > 0;
            }

            $qty = $row['qty'];
            if ($product->isActive() && $product->stock > 0) {
                $qty = min($qty, $product->stock);
            }

            $unit = $product->isActive() ? $product->price_cents : 0;

            $lines[] = [
                'product_id'       => $product->id,
                'qty'              => $qty,
                'product'          => $product,
                'unit_price_cents' => $unit,
                'line_total_cents' => $unit * $qty,
                'available'        => $ok && $product->isActive(),
                'message'          => $message,
            ];
        }

        return $lines;
    }

    public function totalCents(): int
    {
        $sum = 0;
        foreach ($this->lines() as $line) {
            if ($line['available']) {
                $sum += $line['line_total_cents'];
            }
        }

        return $sum;
    }

    /** @return null|string message d’erreur */
    public function add(int $productId, int $qty = 1): ?string
    {
        if ($qty < 1) {
            return 'Quantité invalide.';
        }

        $product = $this->productDao->findById($productId);
        /** @var Product|null $product */
        if ($product === null || !$product->isActive()) {
            return 'Produit introuvable ou inactif.';
        }
        if ($product->stock < 1) {
            return 'Produit en rupture de stock.';
        }

        $items = $this->items();
        $found = false;
        foreach ($items as &$row) {
            if ($row['product_id'] === $productId) {
                $newQty = $row['qty'] + $qty;
                if ($newQty > $product->stock) {
                    return 'Stock insuffisant (max. ' . $product->stock . ').';
                }
                $row['qty'] = $newQty;
                $found = true;
                break;
            }
        }
        unset($row);

        if (!$found) {
            if ($qty > $product->stock) {
                return 'Stock insuffisant (max. ' . $product->stock . ').';
            }
            $items[] = ['product_id' => $productId, 'qty' => $qty];
        }

        $this->persist($items);

        return null;
    }

    /** @return null|string message d’erreur */
    public function update(int $productId, int $qty): ?string
    {
        if ($qty < 1) {
            $this->remove($productId);

            return null;
        }

        $product = $this->productDao->findById($productId);
        /** @var Product|null $product */
        if ($product === null || !$product->isActive()) {
            $this->remove($productId);

            return 'Produit retiré (indisponible).';
        }
        if ($qty > $product->stock) {
            return 'Stock insuffisant (max. ' . $product->stock . ').';
        }

        $items = $this->items();
        $found = false;
        foreach ($items as &$row) {
            if ($row['product_id'] === $productId) {
                $row['qty'] = $qty;
                $found = true;
                break;
            }
        }
        unset($row);

        if (!$found) {
            return 'Ligne introuvable dans le panier.';
        }

        $this->persist($items);

        return null;
    }

    public function remove(int $productId): void
    {
        $items = array_values(array_filter(
            $this->items(),
            static fn (array $row): bool => $row['product_id'] !== $productId,
        ));
        $this->persist($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Remplace le panier (ex. restauration après abandon Stripe).
     *
     * @param list<array{product_id: int, qty: int}> $items
     */
    public function replaceItems(array $items): void
    {
        $clean = [];
        foreach ($items as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $clean[] = ['product_id' => $pid, 'qty' => $qty];
            }
        }
        $this->persist($clean);
    }

    /**
     * @param list<array{product_id: int, qty: int}> $items
     */
    private function persist(array $items): void
    {
        $this->session->set(self::SESSION_KEY, [
            'items'      => array_values($items),
            'updated_at' => date('c'),
        ]);
    }
}
