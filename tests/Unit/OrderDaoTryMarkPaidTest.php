<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dao\OrderDao;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrderDaoTryMarkPaidTest extends TestCase
{
    private OrderDao $orders;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(<<<'SQL'
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL DEFAULT 1,
                total_cents INTEGER NOT NULL DEFAULT 0,
                currency TEXT NOT NULL DEFAULT 'eur',
                status TEXT NOT NULL DEFAULT 'pending',
                payment_method TEXT NOT NULL DEFAULT 'card',
                paypal_order_id TEXT NULL,
                paypal_capture_id TEXT NULL,
                stock_applied INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT ''
            );
        SQL);
        $pdo->exec("INSERT INTO orders (id, status) VALUES (1, 'pending')");
        $this->orders = new OrderDao($pdo);
    }

    public function testTryMarkPaidIsIdempotent(): void
    {
        $this->assertTrue($this->orders->tryMarkPaid(1, [
            'paypal_order_id'   => 'PAY-1',
            'paypal_capture_id' => 'CAP-1',
        ]));
        $this->assertFalse($this->orders->tryMarkPaid(1));

        $order = $this->orders->findById(1);
        $this->assertSame('paid', $order?->status);
        $this->assertSame('PAY-1', $order?->paypal_order_id);
        $this->assertSame('CAP-1', $order?->paypal_capture_id);
    }
}
