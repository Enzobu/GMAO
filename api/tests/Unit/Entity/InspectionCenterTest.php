<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use App\Entity\InspectionCenter;
use App\Entity\VehicleInspection;
use PHPUnit\Framework\TestCase;

final class InspectionCenterTest extends TestCase
{
    public function testAccessorsAndInspectionRelation(): void
    {
        $address = new Address();
        $emptyCenter = new InspectionCenter();
        self::assertNull($emptyCenter->getId());

        $center = (new InspectionCenter())
            ->setName('Controle technique')
            ->setPhone('0102030405')
            ->setEmail('ct@example.com')
            ->setAddress($address);

        self::assertSame('Controle technique', $center->getName());
        self::assertSame('0102030405', $center->getPhone());
        self::assertSame('ct@example.com', $center->getEmail());
        self::assertSame($address, $center->getAddress());

        $inspection = new VehicleInspection();
        $center->addVehicleInspection($inspection);

        self::assertTrue($center->getVehicleInspections()->contains($inspection));
        self::assertSame($center, $inspection->getCenter());

        $center->removeVehicleInspection($inspection);
        self::assertFalse($center->getVehicleInspections()->contains($inspection));
        self::assertNull($inspection->getCenter());

        $center->addVehicleInspection($inspection);
        $center->addVehicleInspection($inspection);
        self::assertCount(1, $center->getVehicleInspections());

        $otherCenter = new InspectionCenter();
        $inspection->setCenter($otherCenter);
        $center->removeVehicleInspection($inspection);
        self::assertSame($otherCenter, $inspection->getCenter());
    }
}
