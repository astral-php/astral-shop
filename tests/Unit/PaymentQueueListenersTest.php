<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\OrderBecamePaid;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Listeners\GenerateInvoiceOnPayment;
use App\Listeners\SendOrderConfirmationEmail;
use Astral\Worker\Queue\QueueInterface;
use Astral\Worker\Queue\QueuedJob;
use PHPUnit\Framework\TestCase;
use Throwable;

final class PaymentQueueListenersTest extends TestCase
{
    public function testListenersPushInvoiceThenConfirmationJobs(): void
    {
        $queue = new class implements QueueInterface {
            /** @var list<array{0: class-string, 1: array<string, mixed>}> */
            public array $pushed = [];

            public function push(string $jobClass, array $payload = [], int $delay = 0, string $queue = 'default'): string|int
            {
                $this->pushed[] = [$jobClass, $payload];

                return count($this->pushed);
            }

            public function pop(string $queue = 'default'): ?QueuedJob
            {
                return null;
            }

            public function delete(QueuedJob $job): void
            {
            }

            public function release(QueuedJob $job, int $delay = 0): void
            {
            }

            public function fail(QueuedJob $job, Throwable $e): void
            {
            }

            public function size(string $queue = 'default'): int
            {
                return 0;
            }

            public function failed(int $limit = 50): array
            {
                return [];
            }

            public function retry(string|int $id): bool
            {
                return false;
            }

            public function retryAll(): int
            {
                return 0;
            }

            public function flushFailed(): int
            {
                return 0;
            }
        };

        (new GenerateInvoiceOnPayment($queue))->handle(new OrderBecamePaid(42));
        (new SendOrderConfirmationEmail($queue))->handle(new OrderBecamePaid(42));

        $this->assertCount(2, $queue->pushed);
        $this->assertSame(GenerateInvoiceJob::class, $queue->pushed[0][0]);
        $this->assertSame(['order_id' => 42], $queue->pushed[0][1]);
        $this->assertSame(SendOrderConfirmationJob::class, $queue->pushed[1][0]);
        $this->assertSame(['order_id' => 42], $queue->pushed[1][1]);
    }
}
