<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Part;
use App\Repository\PartRepository;

final class PartRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(PartRepository::class, $this->instantiateRepository(PartRepository::class, Part::class));
    }

    public function testFindsByFiltersWithVehicleAndPartType(): void
    {
        $parts = [new Part()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($parts);
        $repository = $this->instantiateRepositoryWithQueryBuilder(PartRepository::class, Part::class, $queryBuilder);

        self::assertSame($parts, $repository->findByFilters(12, 34));
        $this->assertRecordedCall($calls, 'leftJoin', ['p.vehicles', 'v']);
        $this->assertRecordedCall($calls, 'leftJoin', ['p.partType', 'pt']);
        $this->assertRecordedCall($calls, 'andWhere', ['v.id = :vehicleId']);
        $this->assertRecordedCall($calls, 'setParameter', ['vehicleId', 12]);
        $this->assertRecordedCall($calls, 'andWhere', ['pt.id = :partTypeId']);
        $this->assertRecordedCall($calls, 'setParameter', ['partTypeId', 34]);
        $this->assertRecordedCall($calls, 'orderBy', ['p.quantity', 'ASC']);
        $this->assertRecordedCall($calls, 'addOrderBy', ['pt.name', 'ASC']);
        $this->assertRecordedCall($calls, 'addOrderBy', ['p.updatedAt', 'DESC']);
    }
}
