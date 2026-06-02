<?php

namespace App\Tests\Unit\Repository;

use App\Entity\VehicleInspection;
use App\Entity\Vehicle;
use App\Repository\VehicleInspectionRepository;

final class VehicleInspectionRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(VehicleInspectionRepository::class, $this->instantiateRepository(VehicleInspectionRepository::class, VehicleInspection::class));
    }

    public function testFindsByVehicleCriteriaWithNotDeletedFiltersAndOrder(): void
    {
        $vehicle = new Vehicle();
        $inspections = [new VehicleInspection()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($inspections);
        $repository = $this->instantiateRepositoryWithQueryBuilder(VehicleInspectionRepository::class, VehicleInspection::class, $queryBuilder);

        self::assertSame($inspections, $repository->findByVehicle(['vehicle' => $vehicle], ['expiresAt' => 'ASC', 'createdAt' => 'invalid']));
        $this->assertRecordedCall($calls, 'innerJoin', ['vi.vehicle', 'v']);
        $this->assertRecordedCall($calls, 'addSelect', ['v']);
        $this->assertRecordedCall($calls, 'andWhere', ['vi.vehicle = :vehicle']);
        $this->assertRecordedCall($calls, 'setParameter', ['vehicle', $vehicle]);
        $this->assertRecordedCall($calls, 'andWhere', ['v.isDeleted = :deleted']);
        $this->assertRecordedCall($calls, 'andWhere', ['vi.isDeleted = :deleted']);
        $this->assertRecordedCall($calls, 'setParameter', ['deleted', false]);
        $this->assertRecordedCall($calls, 'addOrderBy', ['vi.expiresAt', 'ASC']);
        $this->assertRecordedCall($calls, 'addOrderBy', ['vi.createdAt', 'DESC']);
    }

    public function testFindsByVehicleCanIncludeDeletedRows(): void
    {
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder([]);
        $repository = $this->instantiateRepositoryWithQueryBuilder(VehicleInspectionRepository::class, VehicleInspection::class, $queryBuilder);

        self::assertSame([], $repository->findByVehicle(deleted: true));
        self::assertNotContains(['andWhere', ['v.isDeleted = :deleted']], $calls->getArrayCopy());
        self::assertNotContains(['andWhere', ['vi.isDeleted = :deleted']], $calls->getArrayCopy());
    }

    public function testFindsExpiringForReminderDate(): void
    {
        $date = new \DateTimeImmutable('2026-06-09');
        $inspections = [new VehicleInspection()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($inspections);
        $repository = $this->instantiateRepositoryWithQueryBuilder(
            VehicleInspectionRepository::class,
            VehicleInspection::class,
            $queryBuilder,
        );

        self::assertSame($inspections, $repository->findExpiringForReminderDate($date));
        $this->assertRecordedCall($calls, 'innerJoin', ['vi.vehicle', 'v']);
        $this->assertRecordedCall($calls, 'innerJoin', ['v.user', 'u']);
        $this->assertRecordedCall($calls, 'andWhere', ['vi.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'andWhere', ['v.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'andWhere', ['u.isDeleted = :isDeleted']);
        $this->assertRecordedCall($calls, 'andWhere', ['vi.validUntil = :date']);
        $this->assertRecordedCall($calls, 'setParameter', ['isDeleted', false]);
        $this->assertRecordedCall($calls, 'setParameter', ['date', $date]);
        $this->assertRecordedCall($calls, 'orderBy', ['vi.validUntil', 'ASC']);
    }
}
