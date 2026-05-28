<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testAccessors(): void
    {
        $emptyAddress = new Address();
        self::assertNull($emptyAddress->getId());

        $address = (new Address())
            ->setLine1('1 rue de Paris')
            ->setLine2('Batiment A')
            ->setPostalCode('75000')
            ->setCity('Paris')
            ->setCountry('France');

        self::assertSame('1 rue de Paris', $address->getLine1());
        self::assertSame('Batiment A', $address->getLine2());
        self::assertSame('75000', $address->getPostalCode());
        self::assertSame('Paris', $address->getCity());
        self::assertSame('France', $address->getCountry());
    }
}
