<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\VehicleInspectionStateProcessor;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleInspectionStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = $this->createProcessor();

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testOwnerCanPersistInspection(): void
    {
        $user = new User();
        $inspection = (new VehicleInspection())->setVehicle((new Vehicle())->setUser($user));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($inspection);
        $em->expects(self::once())->method('flush');
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(null);
        $vehicleManager->method('syncAfterEventMileageChange')->willReturn(false);

        $result = $this->createProcessor($em, $security, $vehicleManager)->process($inspection, new Post());

        self::assertSame($inspection, $result);
    }

    public function testNonOwnerCannotPersistInspection(): void
    {
        $inspection = (new VehicleInspection())->setVehicle((new Vehicle())->setUser(new User()));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->expectException(AccessDeniedHttpException::class);

        $this->createProcessor($em, $security)->process($inspection, new Post());
    }

    public function testDeleteSoftDeletesInspection(): void
    {
        $inspection = new VehicleInspection();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('syncAfterEventMileageChange')->willReturn(false);

        $result = $this->createProcessor($em, vehicleManager: $vehicleManager)->process($inspection, new Delete());

        self::assertNull($result);
        self::assertTrue($inspection->isDeleted());
    }

    public function testDeleteFlushesAgainWhenMileageIsRecalculated(): void
    {
        $vehicle = (new Vehicle())->setLastMileage(5000);
        $inspection = (new VehicleInspection())->setVehicle($vehicle)->setMileage(5000);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('flush');
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->expects(self::once())->method('syncAfterEventMileageChange')->with($vehicle, 5000, null, null, 5000)->willReturn(true);

        $result = $this->createProcessor($em, vehicleManager: $vehicleManager)->process($inspection, new Delete(), context: ['previous_data' => $inspection]);

        self::assertNull($result);
        self::assertTrue($inspection->isDeleted());
    }

    public function testRejectsMileageBelowKnownMileage(): void
    {
        $user = new User();
        $vehicle = (new Vehicle())->setUser($user)->setLastMileage(5000);
        $inspection = (new VehicleInspection())->setVehicle($vehicle)->setMileage(4000);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(['currentMileage' => 5000, 'submittedMileage' => 4000, 'fieldError' => 'Kilométrage incohérent.']);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Kilométrage incohérent.');

        $this->createProcessor($em, $security, $vehicleManager)->process($inspection, new Post());
    }

    public function testAdminCanForceMileageWarning(): void
    {
        $inspection = (new VehicleInspection())->setVehicle(new Vehicle())->setMileage(4000);
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(['currentMileage' => 5000, 'submittedMileage' => 4000, 'fieldError' => 'Kilométrage incohérent.']);
        $vehicleManager->method('syncAfterEventMileageChange')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($inspection);
        $em->expects(self::once())->method('flush');
        $request = new Request(query: ['forceMileage' => 'true']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $result = $this->createProcessor($em, $security, $vehicleManager, $requestStack)->process($inspection, new Post());

        self::assertSame($inspection, $result);
    }

    public function testSyncsVehicleMileageAfterPersist(): void
    {
        $user = new User();
        $vehicle = (new Vehicle())->setUser($user)->setLastMileage(5000);
        $inspection = (new VehicleInspection())->setVehicle($vehicle)->setMileage(6000);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $vehicleManager = $this->createMock(VehicleManager::class);
        $vehicleManager->method('buildEventMileageWarning')->willReturn(null);
        $vehicleManager->expects(self::once())->method('syncAfterEventMileageChange')->with(null, null, $vehicle, 6000, null)->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($inspection);
        $em->expects(self::exactly(2))->method('flush');

        $result = $this->createProcessor($em, $security, $vehicleManager)->process($inspection, new Post());

        self::assertSame($inspection, $result);
    }

    private function createProcessor(
        ?EntityManagerInterface $entityManager = null,
        ?Security $security = null,
        ?VehicleManager $vehicleManager = null,
        ?RequestStack $requestStack = null,
    ): VehicleInspectionStateProcessor {
        $manager = $vehicleManager;

        if ($manager === null) {
            $manager = $this->createMock(VehicleManager::class);
            $manager->method('buildEventMileageWarning')->willReturn(null);
            $manager->method('syncAfterEventMileageChange')->willReturn(false);
        }

        return new VehicleInspectionStateProcessor(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $security ?? $this->createMock(Security::class),
            $manager,
            $requestStack ?? new RequestStack(),
        );
    }
}
