<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Vehicle;
use App\Repository\VehicleRepository;

final class VehicleRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(VehicleRepository::class, $this->instantiateRepository(VehicleRepository::class, Vehicle::class));
    }

    public function testFindsAllNotDeletedOrderedByNewestId(): void
    {
        $vehicles = [new Vehicle()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($vehicles);
        $repository = $this->instantiateRepositoryWithQueryBuilder(VehicleRepository::class, Vehicle::class, $queryBuilder);

        self::assertSame($vehicles, $repository->findAllNotDeleted());
        $this->assertRecordedCall($calls, 'andWhere', ['v.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'setParameter', ['isDeleted', false]);
        $this->assertRecordedCall($calls, 'orderBy', ['v.id', 'DESC']);
    }
}
