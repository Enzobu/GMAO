<?php

namespace App\Tests\Unit\Repository;

use App\Entity\MaintenancePart;
use App\Repository\MaintenancePartRepository;

final class MaintenancePartRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(MaintenancePartRepository::class, $this->instantiateRepository(MaintenancePartRepository::class, MaintenancePart::class));
    }
}
