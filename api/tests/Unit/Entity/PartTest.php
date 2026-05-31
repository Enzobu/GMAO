<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\PartType;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

final class PartTest extends TestCase
{
    public function testAccessorsAndLifecycleTimestamps(): void
    {
        $part = (new Part())
            ->setPartType(new PartType())
            ->setQuantity(3)
            ->setNote('Stock atelier')
            ->setIsDeleted(true);

        self::assertNull((new Part())->getId());
        self::assertInstanceOf(PartType::class, $part->getPartType());
        self::assertSame(3, $part->getQuantity());
        self::assertSame('Stock atelier', $part->getNote());
        self::assertTrue($part->isDeleted());

        $part->setCreatedAtValue();

        self::assertInstanceOf(\DateTimeImmutable::class, $part->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $part->getUpdatedAt());

        $createdAt = new \DateTimeImmutable('-1 day');
        $updatedAt = new \DateTimeImmutable('-1 hour');
        $part->setCreatedAt($createdAt)->setUpdatedAt($updatedAt);
        self::assertSame($createdAt, $part->getCreatedAt());
        self::assertSame($updatedAt, $part->getUpdatedAt());

        $part->setUpdatedAtValue();
        self::assertNotSame($updatedAt, $part->getUpdatedAt());
    }

    public function testVehicleCollectionCanBeManaged(): void
    {
        $part = new Part();
        $vehicle = new Vehicle();

        $part->addVehicle($vehicle);
        $part->addVehicle($vehicle);
        self::assertTrue($part->getVehicles()->contains($vehicle));
        self::assertCount(1, $part->getVehicles());

        $part->removeVehicle($vehicle);
        self::assertFalse($part->getVehicles()->contains($vehicle));
    }

    public function testRemovingDocumentSoftDeletesIt(): void
    {
        $part = new Part();
        $document = new Document();

        $part->addDocument($document);
        $part->addDocument($document);
        self::assertCount(1, $part->getDocuments());
        $part->removeDocument($document);

        self::assertTrue($document->isDeleted());

        $part->removeDocument($document);
        self::assertTrue($document->isDeleted());
    }

    public function testMaintenancePartRelationIsSetOnAdd(): void
    {
        $part = new Part();
        $maintenancePart = new MaintenancePart();

        $part->addMaintenancePart($maintenancePart);
        $part->addMaintenancePart($maintenancePart);

        self::assertTrue($part->getMaintenanceParts()->contains($maintenancePart));
        self::assertCount(1, $part->getMaintenanceParts());
        self::assertSame($part, $maintenancePart->getPart());

        $part->removeMaintenancePart($maintenancePart);
        self::assertFalse($part->getMaintenanceParts()->contains($maintenancePart));
    }
}
