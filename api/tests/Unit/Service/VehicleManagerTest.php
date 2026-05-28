<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class VehicleManagerTest extends TestCase
{
    public function testAdminIsAlwaysAuthorized(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $manager = new VehicleManager($security, $this->createMock(EntityManagerInterface::class));

        self::assertTrue($manager->isAuthorized(new User(), new Vehicle()));
    }

    public function testOwnerIsAuthorizedWhenNotAdmin(): void
    {
        $user = new User();
        $vehicle = (new Vehicle())->setUser($user);
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(false);

        $manager = new VehicleManager($security, $this->createMock(EntityManagerInterface::class));

        self::assertTrue($manager->isAuthorized($user, $vehicle));
    }

    public function testNonOwnerIsRejectedWhenNotAdmin(): void
    {
        $vehicle = (new Vehicle())->setUser(new User());
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(false);

        $manager = new VehicleManager($security, $this->createMock(EntityManagerInterface::class));

        self::assertFalse($manager->isAuthorized(new User(), $vehicle));
    }

    public function testBuildVehicleMileageWarningReturnsNullWhenMileageIncreases(): void
    {
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildVehicleMileageWarning(1000, 1200));
    }

    public function testBuildVehicleMileageWarningReturnsNullWhenMileageIsMissingOrUnchanged(): void
    {
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildVehicleMileageWarning(null, 1200));
        self::assertNull($manager->buildVehicleMileageWarning(1200, null));
        self::assertNull($manager->buildVehicleMileageWarning(1200, 1200));
    }

    public function testBuildVehicleMileageWarningReturnsPayloadWhenMileageDecreases(): void
    {
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertSame([
            'currentMileage' => 1200,
            'submittedMileage' => 1000,
            'fieldError' => 'Le kilométrage doit être supérieur au dernier kilométrage connu du véhicule (1 200 km).',
        ], $manager->buildVehicleMileageWarning(1200, 1000));
    }

    public function testBuildEventMileageWarningReturnsNullWhenNewMileageIsAboveCurrentMileage(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1000);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildEventMileageWarning(null, null, $vehicle, 1200));
    }

    public function testBuildEventMileageWarningReturnsNullWhenNewVehicleOrMileageIsMissing(): void
    {
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildEventMileageWarning(null, null, null, 1200));
        self::assertNull($manager->buildEventMileageWarning(null, null, new Vehicle(), null));
    }

    public function testBuildEventMileageWarningReturnsNullWhenCurrentMileageIsMissing(): void
    {
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildEventMileageWarning(null, null, new Vehicle(), 1200));
    }

    public function testBuildEventMileageWarningReturnsPayloadWhenNewMileageIsLowerThanCurrentMileage(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1200);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertSame([
            'currentMileage' => 1200,
            'submittedMileage' => 1000,
            'fieldError' => 'Le kilométrage doit être supérieur au dernier kilométrage connu du véhicule (1 200 km).',
        ], $manager->buildEventMileageWarning(null, null, $vehicle, 1000));
    }

    public function testBuildEventMileageWarningIgnoresUnchangedMileageOnSameVehicle(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1200);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildEventMileageWarning($vehicle, 1000, $vehicle, 1000));
    }

    public function testBuildEventMileageWarningWarnsWhenOldMileageWasLastKnownAndNewMileageDrops(): void
    {
        $oldVehicle = (new Vehicle())->setLastMileage(1500);
        $newVehicle = (new Vehicle())->setLastMileage(1500);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertSame([
            'currentMileage' => 1500,
            'submittedMileage' => 1400,
            'fieldError' => 'Le kilométrage doit être supérieur au dernier kilométrage connu du véhicule (1 500 km).',
        ], $manager->buildEventMileageWarning($oldVehicle, 1500, $newVehicle, 1400));
    }

    public function testBuildEventMileageWarningWarnsWhenOldMileageWasLastKnownAndNewMileageIsRemoved(): void
    {
        $oldVehicle = (new Vehicle())->setLastMileage(1500);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertSame([
            'currentMileage' => 1500,
            'submittedMileage' => 1500,
            'fieldError' => 'Le kilométrage doit être supérieur au dernier kilométrage connu du véhicule (1 500 km).',
        ], $manager->buildEventMileageWarning($oldVehicle, 1500, $oldVehicle, null));
    }

    public function testBuildEventMileageWarningTreatsVehiclesWithSameIdAsSameVehicle(): void
    {
        $oldVehicle = $this->vehicleWithId(12)->setLastMileage(1000);
        $newVehicle = $this->vehicleWithId(12)->setLastMileage(1500);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertNull($manager->buildEventMileageWarning($oldVehicle, 1000, $newVehicle, 1000));
    }

    public function testSyncAfterEventMileageChangeUpdatesNewVehicleMileage(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1000);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertTrue($manager->syncAfterEventMileageChange(null, null, $vehicle, 1300, null));
        self::assertSame(1300, $vehicle->getLastMileage());
    }

    public function testSyncAfterEventMileageChangeDoesNotChangeWhenMileageDoesNotIncrease(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1300);
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertFalse($manager->syncAfterEventMileageChange(null, null, $vehicle, 1200, null));
        self::assertSame(1300, $vehicle->getLastMileage());
    }

    public function testSyncAfterEventMileageChangeUpdatesNewVehicleWhenCurrentMileageIsMissing(): void
    {
        $vehicle = new Vehicle();
        $manager = new VehicleManager($this->createMock(Security::class), $this->createMock(EntityManagerInterface::class));

        self::assertTrue($manager->syncAfterEventMileageChange(null, null, $vehicle, 1200, null));
        self::assertSame(1200, $vehicle->getLastMileage());
    }

    public function testSyncAfterEventMileageChangeRecalculatesOldVehicleMileageFromHistory(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1500);
        $manager = new VehicleManager(
            $this->createMock(Security::class),
            $this->entityManagerReturningHistoryMileages('1300', '1400'),
        );

        self::assertTrue($manager->syncAfterEventMileageChange($vehicle, 1500, null, null, 1500));
        self::assertSame(1400, $vehicle->getLastMileage());
    }

    public function testSyncAfterEventMileageChangeDoesNotChangeWhenRecalculatedMileageIsSame(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1500);
        $manager = new VehicleManager(
            $this->createMock(Security::class),
            $this->entityManagerReturningHistoryMileages('1500', null),
        );

        self::assertFalse($manager->syncAfterEventMileageChange($vehicle, 1500, null, null, 1500));
        self::assertSame(1500, $vehicle->getLastMileage());
    }

    public function testSyncAfterEventMileageChangeRecalculatesOldVehicleMileageToNullWhenHistoryIsEmpty(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(1500);
        $manager = new VehicleManager(
            $this->createMock(Security::class),
            $this->entityManagerReturningHistoryMileages(null, null),
        );

        self::assertTrue($manager->syncAfterEventMileageChange($vehicle, 1500, null, null, 1500));
        self::assertNull($vehicle->getLastMileage());
    }

    private function vehicleWithId(int $id): Vehicle
    {
        $vehicle = new Vehicle();
        $property = new \ReflectionProperty(Vehicle::class, 'id');
        $property->setValue($vehicle, $id);

        return $vehicle;
    }

    private function entityManagerReturningHistoryMileages(mixed $maintenanceMileage, mixed $inspectionMileage): EntityManagerInterface
    {
        $maintenanceQueryBuilder = $this->queryBuilderReturningSingleScalarResult($maintenanceMileage);
        $inspectionQueryBuilder = $this->queryBuilderReturningSingleScalarResult($inspectionMileage);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($maintenanceQueryBuilder, $inspectionQueryBuilder);

        return $entityManager;
    }

    private function queryBuilderReturningSingleScalarResult(mixed $result): QueryBuilder
    {
        $query = $this->createMock(Query::class);
        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($result);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}
