<?php

namespace App\Tests\Unit\Repository;

use App\Entity\MaintenanceType;
use App\Repository\MaintenanceTypeRepository;

final class MaintenanceTypeRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(MaintenanceTypeRepository::class, $this->instantiateRepository(MaintenanceTypeRepository::class, MaintenanceType::class));
    }

    public function testFindsAllNotDeletedOrderedByName(): void
    {
        $types = [new MaintenanceType()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($types);
        $repository = $this->instantiateRepositoryWithQueryBuilder(MaintenanceTypeRepository::class, MaintenanceType::class, $queryBuilder);

        self::assertSame($types, $repository->findAllNotDeleted());
        $this->assertRecordedCall($calls, 'andWhere', ['mt.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'setParameter', ['isDeleted', false]);
        $this->assertRecordedCall($calls, 'orderBy', ['mt.name', 'ASC']);
    }
}
