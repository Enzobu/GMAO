<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Address;
use App\Repository\AddressRepository;

final class AddressRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(AddressRepository::class, $this->instantiateRepository(AddressRepository::class, Address::class));
    }
}
