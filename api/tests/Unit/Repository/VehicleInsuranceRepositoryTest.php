<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Repository\VehicleInsuranceRepository;

final class VehicleInsuranceRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(VehicleInsuranceRepository::class, $this->instantiateRepository(VehicleInsuranceRepository::class, VehicleInsurance::class));
    }

    public function testDeactivatesAllForVehicle(): void
    {
        $vehicle = new Vehicle();
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder(executeResult: 3);
        $repository = $this->instantiateRepositoryWithQueryBuilder(VehicleInsuranceRepository::class, VehicleInsurance::class, $queryBuilder);

        self::assertSame(3, $repository->deactivateAllForVehicle($vehicle));
        $this->assertRecordedCall($calls, 'update', []);
        $this->assertRecordedCall($calls, 'set', ['vi.isActive', ':false']);
        $this->assertRecordedCall($calls, 'where', ['vi.vehicle = :vehicle']);
        $this->assertRecordedCall($calls, 'andWhere', ['vi.isActive = :true']);
        $this->assertRecordedCall($calls, 'setParameter', ['vehicle', $vehicle]);
        $this->assertRecordedCall($calls, 'setParameter', ['true', true]);
        $this->assertRecordedCall($calls, 'setParameter', ['false', false]);
    }

    public function testFindsByVehicleCriteriaWithNotDeletedFiltersAndOrder(): void
    {
        $vehicle = new Vehicle();
        $insurances = [new VehicleInsurance()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($insurances);
        $repository = $this->instantiateRepositoryWithQueryBuilder(VehicleInsuranceRepository::class, VehicleInsurance::class, $queryBuilder);

        self::assertSame($insurances, $repository->findByVehicle(['vehicle' => $vehicle], ['expiresAt' => 'ASC', 'createdAt' => 'invalid']));
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
        $repository = $this->instantiateRepositoryWithQueryBuilder(VehicleInsuranceRepository::class, VehicleInsurance::class, $queryBuilder);

        self::assertSame([], $repository->findByVehicle(deleted: true));
        self::assertNotContains(['andWhere', ['v.isDeleted = :deleted']], $calls->getArrayCopy());
        self::assertNotContains(['andWhere', ['vi.isDeleted = :deleted']], $calls->getArrayCopy());
    }
}
