<?php

namespace App\Tests\Unit\Repository;

use App\Entity\InspectionCenter;
use App\Repository\InspectionCenterRepository;

final class InspectionCenterRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(InspectionCenterRepository::class, $this->instantiateRepository(InspectionCenterRepository::class, InspectionCenter::class));
    }
}
