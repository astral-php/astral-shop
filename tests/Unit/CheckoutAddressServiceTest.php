<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dao\UserDao;
use App\Services\Checkout\CheckoutAddressService;
use PDO;
use PHPUnit\Framework\TestCase;

final class CheckoutAddressServiceTest extends TestCase
{
    private CheckoutAddressService $addresses;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $this->addresses = new CheckoutAddressService(new UserDao($pdo));
    }

    public function testParseValidShippingWithBillingSame(): void
    {
        $result = $this->addresses->parseAndValidateFromInput([
            'shipping_name'    => 'Alice Martin',
            'shipping_line1'   => '12 rue des Lilas',
            'shipping_postal'    => '44000',
            'shipping_city'    => 'Nantes',
            'shipping_country' => 'fr',
        ], true);

        $this->assertSame([], $result['errors']);
        $this->assertSame('FR', $result['shipping']['shipping_country']);
        $this->assertSame('Alice Martin', $result['billing']['billing_name']);
        $this->assertTrue($result['billing_same']);
    }

    public function testParseRejectsShortPostal(): void
    {
        $result = $this->addresses->parseAndValidateFromInput([
            'shipping_name'    => 'Alice',
            'shipping_line1'   => '12 rue',
            'shipping_postal'    => '44',
            'shipping_city'    => 'Nantes',
            'shipping_country' => 'FR',
        ], true);

        $this->assertArrayHasKey('shipping_postal', $result['errors']);
    }
}
