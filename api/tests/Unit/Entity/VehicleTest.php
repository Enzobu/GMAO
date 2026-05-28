<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\VehicleColorEnum;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use PHPUnit\Framework\TestCase;

final class VehicleTest extends TestCase
{
    public function testTextFieldsAreLowercasedAndDisplayNameIsFormatted(): void
    {
        $purchaseDate = new \DateTimeImmutable('2024-01-01');
        self::assertNull((new Vehicle())->getId());

        $vehicle = (new Vehicle())
            ->setName('Camion A')
            ->setRegistration('AB-123-CD')
            ->setBrand('Renault')
            ->setModel('Master')
            ->setType(VehicleTypeEnum::Truck)
            ->setYear(2024)
            ->setVin('VF1ABCDEFG1234567')
            ->setEngine('2.0 dCi')
            ->setFuelType(VehicleFuelTypeEnum::Diesel)
            ->setTransmission(VehicleTransmissionTypeEnum::Manual)
            ->setLastMileage(1200)
            ->setColor(VehicleColorEnum::white)
            ->setPurchaseDate($purchaseDate)
            ->setPurchasePrice('12000.50')
            ->setStatus(VehicleStatusEnum::OutOfService)
            ->setUser(new User());

        self::assertSame('camion a', $vehicle->getName());
        self::assertSame('ab-123-cd', $vehicle->getRegistration());
        self::assertSame('renault', $vehicle->getBrand());
        self::assertSame('master', $vehicle->getModel());
        self::assertSame(VehicleTypeEnum::Truck, $vehicle->getType());
        self::assertSame(2024, $vehicle->getYear());
        self::assertSame('VF1ABCDEFG1234567', $vehicle->getVin());
        self::assertSame('2.0 dCi', $vehicle->getEngine());
        self::assertSame(VehicleFuelTypeEnum::Diesel, $vehicle->getFuelType());
        self::assertSame(VehicleTransmissionTypeEnum::Manual, $vehicle->getTransmission());
        self::assertSame(1200, $vehicle->getLastMileage());
        self::assertSame(VehicleColorEnum::white, $vehicle->getColor());
        self::assertSame($purchaseDate, $vehicle->getPurchaseDate());
        self::assertSame('12000.50', $vehicle->getPurchasePrice());
        self::assertSame(VehicleStatusEnum::OutOfService, $vehicle->getStatus());
        self::assertInstanceOf(User::class, $vehicle->getUser());
        self::assertSame('Camion a ・ AB-123-CD', $vehicle->displayName());
    }

    public function testVehicleInsuranceRelationIsSetOnAdd(): void
    {
        $vehicle = new Vehicle();
        $insurance = new VehicleInsurance();

        $vehicle->addVehicleInsurance($insurance);
        $vehicle->addVehicleInsurance($insurance);

        self::assertTrue($vehicle->getVehicleInsurances()->contains($insurance));
        self::assertCount(1, $vehicle->getVehicleInsurances());
        self::assertSame($vehicle, $insurance->getVehicle());

        $vehicle->removeVehicleInsurance($insurance);
        self::assertFalse($vehicle->getVehicleInsurances()->contains($insurance));
    }

    public function testVehicleInspectionRelationIsSetOnAdd(): void
    {
        $vehicle = new Vehicle();
        $inspection = new VehicleInspection();

        $vehicle->addVehicleInspection($inspection);
        $vehicle->addVehicleInspection($inspection);

        self::assertTrue($vehicle->getVehicleInspections()->contains($inspection));
        self::assertCount(1, $vehicle->getVehicleInspections());
        self::assertSame($vehicle, $inspection->getVehicle());

        $vehicle->removeVehicleInspection($inspection);
        self::assertFalse($vehicle->getVehicleInspections()->contains($inspection));
        self::assertNull($inspection->getVehicle());
    }

    public function testRemovingDocumentSoftDeletesIt(): void
    {
        $vehicle = new Vehicle();
        $document = new Document();

        $vehicle->addDocument($document);
        $vehicle->addDocument($document);
        self::assertCount(1, $vehicle->getDocuments());
        $vehicle->removeDocument($document);

        self::assertTrue($document->isDeleted());

        $vehicle->removeDocument($document);
        self::assertTrue($document->isDeleted());
    }

    public function testPartRelationIsBidirectional(): void
    {
        $vehicle = new Vehicle();
        $part = new Part();

        $vehicle->addPart($part);
        $vehicle->addPart($part);

        self::assertTrue($vehicle->getParts()->contains($part));
        self::assertCount(1, $vehicle->getParts());
        self::assertTrue($part->getVehicles()->contains($vehicle));

        $vehicle->removePart($part);

        self::assertFalse($vehicle->getParts()->contains($part));
        self::assertFalse($part->getVehicles()->contains($vehicle));
    }

    public function testMaintenanceRelationIsSetOnAdd(): void
    {
        $vehicle = new Vehicle();
        $maintenance = new Maintenance();

        $vehicle->addMaintenance($maintenance);
        $vehicle->addMaintenance($maintenance);

        self::assertTrue($vehicle->getMaintenances()->contains($maintenance));
        self::assertCount(1, $vehicle->getMaintenances());
        self::assertSame($vehicle, $maintenance->getVehicle());

        $vehicle->removeMaintenance($maintenance);
        self::assertFalse($vehicle->getMaintenances()->contains($maintenance));
        self::assertNull($maintenance->getVehicle());
    }

    public function testSoftDeleteFlagCanBeChanged(): void
    {
        $vehicle = new Vehicle();

        self::assertFalse($vehicle->isDeleted());
        $vehicle->setIsDeleted(true);
        self::assertTrue($vehicle->isDeleted());
    }
}
