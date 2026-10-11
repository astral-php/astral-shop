<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\ProductDao;
use App\Listeners\DecrementStockOnPaymentSucceeded;
use AstralPayment\Events\PaymentSucceeded;
use Core\Logger;
use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DecrementStockOnPaymentSucceededTest extends TestCase
{
    private PDO $pdo;
    private ProductDao $productDao;
    private OrderDao $orderDao;
    private DecrementStockOnPaymentSucceeded $listener;

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
                stripe_intent_id TEXT NULL,
                shipping_name TEXT NOT NULL DEFAULT '',
                shipping_line1 TEXT NOT NULL DEFAULT '',
                shipping_postal TEXT NOT NULL DEFAULT '',
                shipping_city TEXT NOT NULL DEFAULT '',
                shipping_country TEXT NOT NULL DEFAULT 'FR',
                stock_applied INTEGER NOT NULL DEFAULT 0,
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

        $this->pdo->exec("INSERT INTO products (id, name, slug, price_cents, stock) VALUES (10, 'Tee', 'tee', 2000, 8)");
        $this->pdo->exec("INSERT INTO orders (id, user_id, total_cents, status, stock_applied) VALUES (1, 1, 4000, 'paid', 0)");
        $this->pdo->exec(
            "INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents)
             VALUES (1, 10, 'Tee', 2000, 2, 4000)"
        );

        $logDir = sys_get_temp_dir() . '/astral-shop-test-logs';
        $this->productDao = new ProductDao($this->pdo);
        $this->orderDao = new OrderDao($this->pdo);
        $this->listener = new DecrementStockOnPaymentSucceeded(
            pdo: $this->pdo,
            orderDao: $this->orderDao,
            orderItemDao: new OrderItemDao($this->pdo),
            productDao: $this->productDao,
            logger: new Logger($logDir),
        );
    }

    public function testDecrementsStockOnce(): void
    {
        $event = new PaymentSucceeded(new stdClass(), 1);
        $this->listener->handle($event);

        $product = $this->productDao->findById(10);
        $this->assertNotNull($product);
        $this->assertSame(6, (int) $product->stock);

        $order = $this->orderDao->findById(1);
        $this->assertNotNull($order);
        $this->assertSame(1, (int) $order->stock_applied);

        // Idempotent
        $this->listener->handle($event);
        $product = $this->productDao->findById(10);
        $this->assertSame(6, (int) $product->stock);
    }
}
