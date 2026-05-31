<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\MaintenancePart;
use PHPUnit\Framework\TestCase;

final class MaintenancePartTest extends TestCase
{
    public function testAccessorsAndLifecycleTimestamps(): void
    {
        $maintenance = new Maintenance();
        $part = new Part();
        $emptyMaintenancePart = new MaintenancePart();
        self::assertNull($emptyMaintenancePart->getId());

        $maintenancePart = (new MaintenancePart())
            ->setMaintenance($maintenance)
            ->setPart($part)
            ->setQuantity(2)
            ->setNotes('Notes');

        self::assertSame($maintenance, $maintenancePart->getMaintenance());
        self::assertSame($part, $maintenancePart->getPart());
        self::assertSame(2, $maintenancePart->getQuantity());
        self::assertSame('Notes', $maintenancePart->getNotes());

        $maintenancePart->onPrePersist();

        self::assertInstanceOf(\DateTimeImmutable::class, $maintenancePart->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $maintenancePart->getUpdatedAt());

        $createdAt = new \DateTimeImmutable('-1 day');
        $updatedAt = new \DateTimeImmutable('-1 hour');
        $maintenancePart->setCreatedAt($createdAt)->setUpdatedAt($updatedAt);

        self::assertSame($createdAt, $maintenancePart->getCreatedAt());
        self::assertSame($updatedAt, $maintenancePart->getUpdatedAt());

        $maintenancePart->onPreUpdate();
        self::assertNotSame($updatedAt, $maintenancePart->getUpdatedAt());
    }
}
