<?php

namespace App\Tests\Unit\Service;

use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Repository\VehicleInsuranceRepository;
use App\Service\InsuranceManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class InsuranceManagerTest extends TestCase
{
    public function testCreateInsuranceAssociatesVehicleAndActivatesByDefault(): void
    {
        $vehicle = new Vehicle();
        $insurance = new VehicleInsurance();
        $repository = $this->createMock(VehicleInsuranceRepository::class);
        $repository->expects(self::once())->method('deactivateAllForVehicle')->with($vehicle);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $callback): mixed {
                return $callback();
            });
        $em->expects(self::once())->method('persist')->with($insurance);

        (new InsuranceManager($em, $repository))->createInsurance($vehicle, $insurance);

        self::assertSame($vehicle, $insurance->getVehicle());
        self::assertTrue($insurance->isActive());
    }

    public function testCreateInsuranceCanSkipActivation(): void
    {
        $vehicle = new Vehicle();
        $insurance = new VehicleInsurance();
        $repository = $this->createMock(VehicleInsuranceRepository::class);
        $repository->expects(self::never())->method('deactivateAllForVehicle');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $em->expects(self::once())->method('persist')->with($insurance);

        (new InsuranceManager($em, $repository))->createInsurance($vehicle, $insurance, false);

        self::assertSame($vehicle, $insurance->getVehicle());
        self::assertTrue($insurance->isActive());
    }

    public function testActivateInsuranceDeactivatesOthersAndPersists(): void
    {
        $vehicle = new Vehicle();
        $insurance = (new VehicleInsurance())->setVehicle($vehicle);
        $repository = $this->createMock(VehicleInsuranceRepository::class);
        $repository->expects(self::once())->method('deactivateAllForVehicle')->with($vehicle);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $em->expects(self::once())->method('persist')->with($insurance);

        (new InsuranceManager($em, $repository))->activateInsurance($insurance);

        self::assertTrue($insurance->isActive());
    }
}
