<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\InspectionCenter;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\MaintenanceType;
use App\Entity\Part;
use App\Entity\PartType;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\InspectionResultEnum;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\MaintenanceStatusEnum;
use App\Enum\VehicleColorEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTypeEnum;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixturesTest extends TestCase
{
    private const PART_TYPE_ENGINE_OIL_5W40 = 'Huile moteur 5W40';
    private const PART_TYPE_OIL_FILTER = 'Filtre a huile';
    private const PART_TYPE_REINFORCED_CHAIN_KIT = 'Kit chaine renforcé';
    private const REPAIR_NOTE_TURBO_SENSOR = 'Remplacement capteur pression turbo.';

    public function testLoadReturnsImmediatelyWhenModeIsUnsupported(): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $this->createFixtures('disabled')->load($manager);
    }

    public function testLoadPersistsOnlyUsersWhenModeIsUserOnly(): void
    {
        $persisted = [];
        $manager = $this->createCollectingManager($persisted);

        $this->createFixtures('user_only')->load($manager);

        self::assertCount(2, $persisted);
        self::assertContainsOnlyInstancesOf(User::class, $persisted);

        /** @var User $admin */
        $admin = $persisted[0];
        /** @var User $user */
        $user = $persisted[1];

        self::assertSame('palermo.enzo.ep@gmail.com', $admin->getEmail());
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $admin->getRoles());
        self::assertSame('hashed-palermo.enzo.ep@gmail.com-vR2gP5kykK', $admin->getPassword());
        self::assertSame('gio.alex.pa@gmail.com', $user->getEmail());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame('hashed-gio.alex.pa@gmail.com-vR2gP5kykK', $user->getPassword());
    }

    public function testLoadPersistsCompleteFixtureGraphWhenModeIsAll(): void
    {
        $persisted = [];
        $manager = $this->createCollectingManager($persisted);

        $this->createFixtures('all')->load($manager);

        $users = $this->filterByClass($persisted, User::class);
        $vehicles = $this->filterByClass($persisted, Vehicle::class);
        $inspectionCenters = $this->filterByClass($persisted, InspectionCenter::class);
        $partTypes = $this->filterByClass($persisted, PartType::class);
        $parts = $this->filterByClass($persisted, Part::class);
        $maintenanceTypes = $this->filterByClass($persisted, MaintenanceType::class);
        $insurances = $this->filterByClass($persisted, VehicleInsurance::class);
        $inspections = $this->filterByClass($persisted, VehicleInspection::class);
        $maintenances = $this->filterByClass($persisted, Maintenance::class);
        $maintenanceParts = $this->filterByClass($persisted, MaintenancePart::class);

        self::assertCount(2, $users);
        self::assertCount(7, $vehicles);
        self::assertCount(2, $inspectionCenters);
        self::assertCount(12, $partTypes);
        self::assertCount(12, $parts);
        self::assertCount(9, $maintenanceTypes);
        self::assertCount(14, $insurances);
        self::assertCount(5, $inspections);
        self::assertNotEmpty($maintenances);
        self::assertNotEmpty($maintenanceParts);

        /** @var User $admin */
        $admin = $users[0];
        /** @var Vehicle $porsche */
        $porsche = $vehicles[0];
        /** @var Vehicle $nissan */
        $nissan = $vehicles[4];
        /** @var Vehicle $s1000rr */
        $s1000rr = $vehicles[5];

        self::assertSame($admin, $porsche->getUser());
        self::assertSame('911 carrera s', $porsche->getName());
        self::assertSame('gt-911-cs', $porsche->getRegistration());
        self::assertSame(VehicleTypeEnum::Car, $porsche->getType());
        self::assertSame(VehicleColorEnum::Gray, $porsche->getColor());
        self::assertSame(VehicleStatusEnum::Active, $porsche->getStatus());
        self::assertSame('2017-09-18', $porsche->getPurchaseDate()?->format('Y-m-d'));
        self::assertSame(VehicleColorEnum::white, $nissan->getColor());
        self::assertSame(VehicleTypeEnum::Motorcycle, $s1000rr->getType());

        $partByName = [];
        foreach ($parts as $part) {
            self::assertInstanceOf(PartType::class, $part->getPartType());
            $partByName[$part->getPartType()->getName()] = $part;
        }

        self::assertSame(8, $partByName[self::PART_TYPE_ENGINE_OIL_5W40]->getQuantity());
        self::assertSame('Bidons 1L pour appoints et vidanges voitures sportives', $partByName[self::PART_TYPE_ENGINE_OIL_5W40]->getNote());
        self::assertCount(5, $partByName[self::PART_TYPE_ENGINE_OIL_5W40]->getVehicles());
        self::assertNull($partByName[self::PART_TYPE_OIL_FILTER]);

        $activePorscheInsurance = null;
        $oldPorscheInsurance = null;
        $defaultEndDateInsurance = null;
        foreach ($insurances as $insurance) {
            self::assertContains($insurance->getVehicle(), $vehicles, true);

            if ($insurance->getPolicyNumber() === 'ACT-PORSCHE-911-2026') {
                $activePorscheInsurance = $insurance;
            }

            if ($insurance->getPolicyNumber() === 'OLD-PORSCHE-911-2017') {
                $oldPorscheInsurance = $insurance;
            }

            if ($insurance->getPolicyNumber() === 'ACT-BMW-M3-2026') {
                $defaultEndDateInsurance = $insurance;
            }
        }

        self::assertInstanceOf(VehicleInsurance::class, $activePorscheInsurance);
        self::assertSame(InsurancePaymentFrequencyEnum::Monthly, $activePorscheInsurance->getPaymentFrequency());
        self::assertSame('2026-06-12', $activePorscheInsurance->getEndDate()?->format('Y-m-d'));
        self::assertInstanceOf(VehicleInsurance::class, $oldPorscheInsurance);
        self::assertSame(InsurancePaymentFrequencyEnum::Yearly, $oldPorscheInsurance->getPaymentFrequency());
        self::assertSame('2025-12-31', $oldPorscheInsurance->getEndDate()?->format('Y-m-d'));
        self::assertInstanceOf(VehicleInsurance::class, $defaultEndDateInsurance);
        self::assertSame('2026-12-31', $defaultEndDateInsurance->getEndDate()?->format('Y-m-d'));

        $counterVisit = null;
        $passedInspection = null;
        foreach ($inspections as $inspection) {
            if ($inspection->getResult() === InspectionResultEnum::CounterVisit) {
                $counterVisit = $inspection;
            } else {
                $passedInspection = $inspection;
            }
        }

        self::assertInstanceOf(VehicleInspection::class, $counterVisit);
        self::assertTrue($counterVisit->isCounterVisitRequired());
        self::assertSame('2026-01-05', $counterVisit->getCounterVisitDueAt()?->format('Y-m-d'));
        self::assertSame('Contre-visite ancienne, points de freinage a reprendre.', $counterVisit->getNotes());
        self::assertInstanceOf(VehicleInspection::class, $passedInspection);
        self::assertFalse($passedInspection->isCounterVisitRequired());
        self::assertNull($passedInspection->getCounterVisitDueAt());
        self::assertSame('RAS', $passedInspection->getNotes());

        $completedWithFiveLiters = null;
        $motorcycleTransmissionPart = null;
        $plannedMaintenance = null;
        $repair = null;

        foreach ($maintenances as $maintenance) {
            if ($maintenance->getStatus() === MaintenanceStatusEnum::ToDo) {
                $plannedMaintenance = $maintenance;
            }

            if ($maintenance->getNotes() === self::REPAIR_NOTE_TURBO_SENSOR) {
                $repair = $maintenance;
            }
        }

        foreach ($maintenanceParts as $maintenancePart) {
            if ($maintenancePart->getQuantity() === 5) {
                $completedWithFiveLiters = $maintenancePart;
            }

            if ($maintenancePart->getPart()->getPartType()->getName() === self::PART_TYPE_REINFORCED_CHAIN_KIT) {
                $motorcycleTransmissionPart = $maintenancePart;
            }
        }

        self::assertInstanceOf(MaintenancePart::class, $completedWithFiveLiters);
        self::assertSame(self::PART_TYPE_ENGINE_OIL_5W40, $completedWithFiveLiters->getPart()->getPartType()->getName());
        self::assertNull($completedWithFiveLiters->getNotes());
        self::assertInstanceOf(MaintenancePart::class, $motorcycleTransmissionPart);
        self::assertSame(1, $motorcycleTransmissionPart->getQuantity());

        self::assertInstanceOf(Maintenance::class, $plannedMaintenance);
        self::assertSame(MaintenanceStatusEnum::ToDo, $plannedMaintenance->getStatus());
        self::assertNull($plannedMaintenance->getStartedAt());
        self::assertNull($plannedMaintenance->getFinishedAt());
        self::assertFalse($plannedMaintenance->isExternal());

        self::assertInstanceOf(Maintenance::class, $repair);
        self::assertSame(MaintenanceStatusEnum::Completed, $repair->getStatus());
        self::assertTrue($repair->isExternal());
        self::assertNull($repair->getNextDueMileage());
        self::assertNull($repair->getNextDueAt());
    }

    private function createFixtures(string $mode): AppFixtures
    {
        $params = $this->createMock(ContainerBagInterface::class);
        $params->expects(self::once())
            ->method('get')
            ->with('laod_datafixtures')
            ->willReturn($mode);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')
            ->willReturnCallback(static fn (User $user, string $plainPassword): string => sprintf(
                'hashed-%s-%s',
                $user->getEmail(),
                $plainPassword,
            ));

        return new AppFixtures($params, $passwordHasher);
    }

    /**
     * @param list<object> $persisted
     * @return ObjectManager&MockObject
     */
    private function createCollectingManager(array &$persisted): ObjectManager&MockObject
    {
        $manager = $this->createMock(ObjectManager::class);
        $manager->method('persist')
            ->willReturnCallback(static function (object $object) use (&$persisted): void {
                $persisted[] = $object;
            });

        return $manager;
    }

    /**
     * @template T of object
     * @param list<object> $objects
     * @param class-string<T> $className
     * @return list<T>
     */
    private function filterByClass(array $objects, string $className): array
    {
        return array_values(array_filter($objects, static fn (object $object): bool => $object instanceof $className));
    }
}