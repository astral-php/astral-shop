<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Models\Order;
use App\Services\OrderStockService;
use Core\Logger;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrderStockServiceTest extends TestCase
{
    private PDO $pdo;
    private ProductDao $productDao;
    private OrderDao $orderDao;
    private OrderStockService $stock;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(<<<'SQL'
            CREATE TABLE products (
                id INTEGER PRIMARY KEY,
                category_id INTEGER NULL,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT NULL,
                price_cents INTEGER NOT NULL,
                currency TEXT NOT NULL DEFAULT 'EUR',
                stock INTEGER NOT NULL DEFAULT 0,
                sku TEXT NULL,
                image_path TEXT NULL,
                image_url TEXT NULL,
                active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT '',
                updated_at TEXT NOT NULL DEFAULT ''
            );
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                total_cents INTEGER NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT 'eur',
                status TEXT NOT NULL DEFAULT 'pending',
                payment_method TEXT NOT NULL DEFAULT 'check',
                stripe_intent_id TEXT NULL,
                stock_applied INTEGER NOT NULL DEFAULT 0,
                payment_due_at TEXT NULL,
                created_at TEXT NOT NULL DEFAULT ''
            );
            CREATE TABLE order_items (
                id INTEGER PRIMARY KEY,
                order_id INTEGER NOT NULL,
                product_id INTEGER NULL,
                product_name TEXT NOT NULL,
                unit_price_cents INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                line_total_cents INTEGER NOT NULL
            );
        SQL);

        $this->pdo->exec("INSERT INTO products (id, name, slug, price_cents, stock) VALUES (10, 'Tee', 'tee', 2000, 3)");
        $this->pdo->exec("INSERT INTO orders (id, user_id, total_cents, status, payment_method, stock_applied) VALUES (1, 1, 2000, 'pending', 'check', 0)");
        $this->pdo->exec(
            "INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents)
             VALUES (1, 10, 'Tee', 2000, 1, 2000)"
        );

        $this->productDao = new ProductDao($this->pdo);
        $this->orderDao = new OrderDao($this->pdo);
        $this->stock = new OrderStockService(
            pdo: $this->pdo,
            orderDao: $this->orderDao,
            orderItemDao: new OrderItemDao($this->pdo),
            productDao: $this->productDao,
            logger: new Logger(sys_get_temp_dir() . '/astral-shop-test-logs'),
        );
    }

    public function testApplyAndReleaseReservation(): void
    {
        $this->assertTrue($this->stock->applyReservation(1));
        $product = $this->productDao->findById(10);
        $this->assertSame(2, (int) $product?->stock);
        $this->assertFalse($this->stock->applyReservation(1));

        $this->assertTrue($this->stock->releaseReservation(1));
        $product = $this->productDao->findById(10);
        $this->assertSame(3, (int) $product?->stock);
        $this->assertFalse($this->stock->releaseReservation(1));
    }

    public function testCancelAndRelease(): void
    {
        $this->stock->applyReservation(1);
        $order = $this->orderDao->findById(1);
        $this->assertInstanceOf(Order::class, $order);

        $result = $this->stock->cancelAndRelease($order);
        $this->assertTrue($result['ok']);

        $order = $this->orderDao->findById(1);
        $this->assertSame(Order::STATUS_CANCELLED, $order?->status);
        $this->assertSame(3, (int) $this->productDao->findById(10)?->stock);
        $this->assertSame(0, (int) $order?->stock_applied);
    }

    public function testApplyFailsWhenStockInsufficientAndRollsBackFlag(): void
    {
        $this->pdo->exec('UPDATE products SET stock = 0 WHERE id = 10');
        $this->pdo->exec(
            "INSERT INTO orders (id, user_id, total_cents, status, payment_method, stock_applied)
             VALUES (2, 1, 2000, 'pending', 'card', 0)",
        );
        $this->pdo->exec(
            "INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents)
             VALUES (2, 10, 'Tee', 2000, 1, 2000)",
        );

        try {
            $this->stock->applyReservation(2);
            $this->fail('RuntimeException attendue (stock insuffisant)');
        } catch (\RuntimeException) {
            $order = $this->orderDao->findById(2);
            $this->assertSame(0, (int) $order?->stock_applied);
            $this->assertSame(0, (int) $this->productDao->findById(10)?->stock);
        }
    }

    public function testLastUnitOnlyOneOrderGetsStock(): void
    {
        $this->pdo->exec('UPDATE products SET stock = 1 WHERE id = 10');
        $this->pdo->exec(
            "INSERT INTO orders (id, user_id, total_cents, status, payment_method, stock_applied)
             VALUES (2, 1, 2000, 'pending', 'card', 0)",
        );
        $this->pdo->exec(
            "INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents)
             VALUES (2, 10, 'Tee', 2000, 1, 2000)",
        );

        $this->assertTrue($this->stock->applyReservation(1));
        $this->assertSame(0, (int) $this->productDao->findById(10)?->stock);

        $this->expectException(\RuntimeException::class);
        $this->stock->applyReservation(2);
    }
}
