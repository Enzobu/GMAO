<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Maintenance;
use App\Entity\MaintenanceType;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Repository\MaintenanceRepository;

final class MaintenanceRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(MaintenanceRepository::class, $this->instantiateRepository(MaintenanceRepository::class, Maintenance::class));
    }

    public function testFindsByFiltersWithAllOptionalCriteriaAndSorts(): void
    {
        $maintenances = [new Maintenance()];
        $type = new MaintenanceType();
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($maintenances);
        $repository = $this->instantiateRepositoryWithQueryBuilder(MaintenanceRepository::class, Maintenance::class, $queryBuilder);

        self::assertSame($maintenances, $repository->findByFilters(9, $type, MaintenanceStatusEnum::Completed, '  Oil  ', 'vehicle', 'asc'));
        $this->assertRecordedCall($calls, 'join', ['m.vehicle', 'v']);
        $this->assertRecordedCall($calls, 'join', ['m.maintenanceType', 'mt']);
        $this->assertRecordedCall($calls, 'andWhere', ['m.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'setParameter', ['isDeleted', false]);
        $this->assertRecordedCall($calls, 'andWhere', ['v.id = :vehicleId']);
        $this->assertRecordedCall($calls, 'setParameter', ['vehicleId', 9]);
        $this->assertRecordedCall($calls, 'andWhere', ['m.maintenanceType = :maintenanceType']);
        $this->assertRecordedCall($calls, 'setParameter', ['maintenanceType', $type]);
        $this->assertRecordedCall($calls, 'andWhere', ['m.status = :status']);
        $this->assertRecordedCall($calls, 'setParameter', ['status', MaintenanceStatusEnum::Completed]);
        $this->assertRecordedCall($calls, 'andWhere', ['LOWER(v.name) LIKE :query OR LOWER(v.registration) LIKE :query OR LOWER(m.notes) LIKE :query']);
        $this->assertRecordedCall($calls, 'setParameter', ['query', '%oil%']);
        $this->assertRecordedCall($calls, 'orderBy', ['v.name', 'ASC']);
        $this->assertRecordedCall($calls, 'addOrderBy', ['m.createdAt', 'DESC']);
    }

    public function testFindsByFiltersFallsBackToDefaultSortAndDirection(): void
    {
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder([]);
        $repository = $this->instantiateRepositoryWithQueryBuilder(MaintenanceRepository::class, Maintenance::class, $queryBuilder);

        self::assertSame([], $repository->findByFilters(sort: 'unknown', direction: 'sideways'));
        $this->assertRecordedCall($calls, 'orderBy', ['m.createdAt', 'DESC']);
    }

    public function testFindsForVehicle(): void
    {
        $vehicle = new Vehicle();
        $maintenances = [new Maintenance()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($maintenances);
        $repository = $this->instantiateRepositoryWithQueryBuilder(MaintenanceRepository::class, Maintenance::class, $queryBuilder);

        self::assertSame($maintenances, $repository->findForVehicle($vehicle));
        $this->assertRecordedCall($calls, 'join', ['m.maintenanceType', 'mt']);
        $this->assertRecordedCall($calls, 'andWhere', ['m.vehicle = :vehicle']);
        $this->assertRecordedCall($calls, 'andWhere', ['m.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'setParameter', ['vehicle', $vehicle]);
        $this->assertRecordedCall($calls, 'setParameter', ['isDeleted', false]);
        $this->assertRecordedCall($calls, 'orderBy', ['m.finishedAt', 'DESC']);
        $this->assertRecordedCall($calls, 'addOrderBy', ['m.plannedAt', 'DESC']);
        $this->assertRecordedCall($calls, 'addOrderBy', ['m.createdAt', 'DESC']);
    }

    public function testFindsLatestPerformedByVehicle(): void
    {
        $vehicle = new Vehicle();
        $maintenance = new Maintenance();
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder(oneOrNullResult: $maintenance);
        $repository = $this->instantiateRepositoryWithQueryBuilder(MaintenanceRepository::class, Maintenance::class, $queryBuilder);

        self::assertSame($maintenance, $repository->findLatestPerformedByVehicle($vehicle));
        $this->assertRecordedCall($calls, 'andWhere', ['m.finishedAt IS NOT NULL']);
        $this->assertRecordedCall($calls, 'orderBy', ['m.finishedAt', 'DESC']);
        $this->assertRecordedCall($calls, 'setMaxResults', [1]);
    }
}
