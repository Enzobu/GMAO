<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\MaintenanceStateProcessor;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class MaintenanceStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        self::assertNull($this->processor()->process(new \stdClass(), new Post()));
    }

    public function testNonOwnerCannotCreateMaintenance(): void
    {
        $maintenance = (new Maintenance())->setVehicle((new Vehicle())->setUser(new User()));
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn(new User());

        $this->expectException(AccessDeniedHttpException::class);

        $this->processor(security: $security)->process($maintenance, new Post());
    }

    public function testCompletedMaintenanceRequiresFinishedAt(): void
    {
        $security = $this->adminSecurity();
        $maintenance = (new Maintenance())
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setMileage(1000);

        $this->expectException(BadRequestHttpException::class);

        $this->processor(security: $security)->process($maintenance, new Post());
    }

    public function testCompletedMaintenanceRequiresMileage(): void
    {
        $security = $this->adminSecurity();
        $maintenance = (new Maintenance())
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable());

        $this->expectException(BadRequestHttpException::class);

        $this->processor(security: $security)->process($maintenance, new Post());
    }

    public function testCreatesMaintenanceAndNormalizesIncompleteCompletionFields(): void
    {
        $maintenance = (new Maintenance())
            ->setStatus(MaintenanceStatusEnum::ToDo)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1200);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($maintenance);
        $em->expects(self::once())->method('flush');

        $result = $this->processor($em, $this->adminSecurity())->process($maintenance, new Post());

        self::assertSame($maintenance, $result);
        self::assertNull($maintenance->getFinishedAt());
        self::assertNull($maintenance->getMileage());
    }

    public function testOwnerCanCreateCompletedMaintenanceAndConsumesStock(): void
    {
        $user = new User();
        $vehicle = (new Vehicle())->setUser($user)->setLastMileage(1000);
        $this->setId($vehicle, 1);
        $part = (new Part())->setQuantity(5)->addVehicle($vehicle);
        $this->setId($part, 10);
        $maintenancePart = (new MaintenancePart())->setPart($part)->setQuantity(2);
        $maintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1500)
            ->addMaintenancePart($maintenancePart);
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn($user);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($maintenance);
        $em->expects(self::exactly(2))->method('flush');
        $em->method('find')->with(Part::class, 10)->willReturn($part);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(null);
        $vehicleManager->expects(self::once())->method('syncAfterEventMileageChange')->willReturn(true);

        $result = $this->processor($em, $security, $vehicleManager)->process($maintenance, new Post());

        self::assertSame($maintenance, $result);
        self::assertSame(3, $part->getQuantity());
        self::assertSame($maintenance, $maintenancePart->getMaintenance());
    }

    public function testCompletedMaintenanceRejectsMissingPartLine(): void
    {
        $vehicle = new Vehicle();
        $maintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1500)
            ->addMaintenancePart((new MaintenancePart())->setQuantity(1));

        $this->expectException(BadRequestHttpException::class);

        $this->processor(security: $this->adminSecurity())->process($maintenance, new Post());
    }

    public function testCompletedMaintenanceRejectsIncompatiblePart(): void
    {
        $vehicle = new Vehicle();
        $this->setId($vehicle, 1);
        $part = (new Part())->setQuantity(5);
        $this->setId($part, 10);
        $maintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1500)
            ->addMaintenancePart((new MaintenancePart())->setPart($part)->setQuantity(1));

        $this->expectException(BadRequestHttpException::class);

        $this->processor(security: $this->adminSecurity())->process($maintenance, new Post());
    }

    public function testCompletedMaintenanceRejectsInsufficientStock(): void
    {
        $vehicle = new Vehicle();
        $this->setId($vehicle, 1);
        $part = (new Part())->setQuantity(1)->addVehicle($vehicle);
        $this->setId($part, 10);
        $maintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1500)
            ->addMaintenancePart((new MaintenancePart())->setPart($part)->setQuantity(2));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->with(Part::class, 10)->willReturn($part);

        $this->expectException(ConflictHttpException::class);

        $this->processor($em, $this->adminSecurity())->process($maintenance, new Post());
    }

    public function testMileageWarningThrowsConflictWithoutAdminForce(): void
    {
        $user = new User();
        $maintenance = (new Maintenance())->setVehicle((new Vehicle())->setUser($user))->setStatus(MaintenanceStatusEnum::ToDo);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(['fieldError' => 'Kilometrage incoherent.']);
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn($user);

        $this->expectException(ConflictHttpException::class);

        $this->processor(security: $security, vehicleManager: $vehicleManager)->process($maintenance, new Post());
    }

    public function testAdminForceMileageAllowsWarning(): void
    {
        $maintenance = (new Maintenance())->setStatus(MaintenanceStatusEnum::ToDo);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($maintenance);
        $em->expects(self::once())->method('flush');
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(['fieldError' => 'Kilometrage incoherent.']);
        $vehicleManager->method('syncAfterEventMileageChange')->willReturn(false);
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/maintenances?forceMileage=1', 'POST'));

        $result = $this->processor($em, $this->adminSecurity(), $vehicleManager, $requestStack)->process($maintenance, new Post());

        self::assertSame($maintenance, $result);
    }

    public function testDeleteRequiresAdmin(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);

        $this->processor(security: $security)->process(new Maintenance(), new Delete());
    }

    public function testDeleteSoftDeletesMaintenance(): void
    {
        $maintenance = new Maintenance();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $result = $this->processor($em, $this->adminSecurity())->process($maintenance, new Delete(), context: ['previous_data' => $maintenance]);

        self::assertNull($result);
        self::assertTrue($maintenance->isDeleted());
    }

    public function testDeleteRestoresPreviousStockAndSyncsMileage(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(2000);
        $this->setId($vehicle, 1);
        $part = (new Part())->setQuantity(3)->addVehicle($vehicle);
        $this->setId($part, 10);
        $previousMaintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1800)
            ->addMaintenancePart((new MaintenancePart())->setPart($part)->setQuantity(2));
        $maintenance = new Maintenance();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->with(Part::class, 10)->willReturn($part);
        $em->expects(self::exactly(2))->method('flush');
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->expects(self::once())
            ->method('syncAfterEventMileageChange')
            ->with($vehicle, 1800, null, null, 2000)
            ->willReturn(true);

        $result = $this->processor($em, $this->adminSecurity(), $vehicleManager)->process($maintenance, new Delete(), context: ['previous_data' => $previousMaintenance]);

        self::assertNull($result);
        self::assertTrue($maintenance->isDeleted());
        self::assertSame(5, $part->getQuantity());
    }

    public function testUpdatingCompletedMaintenanceWithSameStockSkipsStockLookup(): void
    {
        $vehicle = new Vehicle();
        $this->setId($vehicle, 1);
        $part = (new Part())->setQuantity(3)->addVehicle($vehicle);
        $this->setId($part, 10);
        $previousMaintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1000)
            ->addMaintenancePart((new MaintenancePart())->setPart($part)->setQuantity(2));
        $maintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1000)
            ->addMaintenancePart((new MaintenancePart())->setPart($part)->setQuantity(2));
        $this->setId($maintenance, 20);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('find');
        $em->expects(self::once())->method('flush');

        $result = $this->processor($em, $this->adminSecurity())->process($maintenance, new Patch(), context: ['previous_data' => $previousMaintenance]);

        self::assertSame($maintenance, $result);
        self::assertSame(3, $part->getQuantity());
    }

    public function testStockDeltaSkipsMissingPart(): void
    {
        $part = new Part();
        $this->setId($part, 10);
        $previousMaintenance = (new Maintenance())
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1000)
            ->addMaintenancePart((new MaintenancePart())->setPart($part)->setQuantity(2));
        $maintenance = (new Maintenance())->setStatus(MaintenanceStatusEnum::ToDo);
        $this->setId($maintenance, 20);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('find')->with(Part::class, 10)->willReturn(null);
        $em->expects(self::once())->method('flush');

        $result = $this->processor($em, $this->adminSecurity())->process($maintenance, new Patch(), context: ['previous_data' => $previousMaintenance]);

        self::assertSame($maintenance, $result);
    }

    public function testStockMapSkipsPartWithoutId(): void
    {
        $maintenance = (new Maintenance())
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setFinishedAt(new \DateTimeImmutable())
            ->setMileage(1000)
            ->addMaintenancePart((new MaintenancePart())->setPart(new Part())->setQuantity(2));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('find');
        $em->expects(self::once())->method('persist')->with($maintenance);
        $em->expects(self::once())->method('flush');

        $result = $this->processor($em, $this->adminSecurity())->process($maintenance, new Post());

        self::assertSame($maintenance, $result);
    }

    private function processor(
        ?EntityManagerInterface $em = null,
        ?Security $security = null,
        ?VehicleManager $vehicleManager = null,
        ?RequestStack $requestStack = null,
    ): MaintenanceStateProcessor {
        $vehicleManager ??= $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(null);
        $vehicleManager->method('syncAfterEventMileageChange')->willReturn(false);

        return new MaintenanceStateProcessor(
            $em ?? $this->createMock(EntityManagerInterface::class),
            $security ?? $this->adminSecurity(),
            $vehicleManager,
            $requestStack ?? new RequestStack(),
        );
    }

    private function adminSecurity(bool $isAdmin = true): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn($isAdmin);

        return $security;
    }

    private function setId(object $object, int $id): void
    {
        $property = new \ReflectionProperty($object, 'id');
        $property->setValue($object, $id);
    }
}
