<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dao\ProductDao;
use App\Services\CartService;
use Core\Session;
use PDO;
use PHPUnit\Framework\TestCase;

final class CartServiceTest extends TestCase
{
    private PDO $pdo;
    private CartService $cart;

    protected function setUp(): void
    {
        $_SESSION = [];

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
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        $this->pdo->exec(
            "INSERT INTO products (id, name, slug, price_cents, stock, active)
             VALUES (1, 'Pull', 'pull', 4500, 5, 1)"
        );

        $session = new Session();
        $this->cart = new CartService($session, new ProductDao($this->pdo));
    }

    public function testAddAndTotal(): void
    {
        $this->assertNull($this->cart->add(1, 2));
        $this->assertSame(2, $this->cart->count());
        $this->assertSame(9000, $this->cart->totalCents());
    }

    public function testRefuseOverstock(): void
    {
        $err = $this->cart->add(1, 6);
        $this->assertNotNull($err);
        $this->assertTrue($this->cart->isEmpty());
    }

    public function testUpdateAndClear(): void
    {
        $this->cart->add(1, 1);
        $this->assertNull($this->cart->update(1, 3));
        $this->assertSame(3, $this->cart->count());
        $this->cart->clear();
        $this->assertTrue($this->cart->isEmpty());
    }
}
