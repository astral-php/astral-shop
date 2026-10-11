<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testFormatCentsFrenchEuro(): void
    {
        $out = Money::formatCents(12900, '€');

        $this->assertStringContainsString('129,00', $out);
        $this->assertStringEndsWith('€', $out);
        $this->assertStringNotContainsString('Ã', $out);
    }

    public function testParseEurosToCents(): void
    {
        $this->assertSame(2490, Money::parseEurosToCents('24,90'));
        $this->assertSame(2490, Money::parseEurosToCents('24.90'));
        $this->assertSame(12900, Money::parseEurosToCents('129'));
        $this->assertNull(Money::parseEurosToCents(''));
        $this->assertNull(Money::parseEurosToCents('-1'));
    }
}
