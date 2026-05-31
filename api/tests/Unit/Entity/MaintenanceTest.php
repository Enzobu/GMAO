<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\MaintenanceType;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class MaintenanceTest extends TestCase
{
    public function testAccessorsAndLifecycleTimestamps(): void
    {
        $startedAt = new \DateTimeImmutable('2025-01-01');
        $finishedAt = new \DateTimeImmutable('2025-01-02');
        $plannedAt = new \DateTimeImmutable('2024-12-31');
        $nextDueAt = new \DateTimeImmutable('2025-06-01');
        self::assertNull((new Maintenance())->getId());

        $maintenance = (new Maintenance())
            ->setVehicle(new Vehicle())
            ->setMaintenanceType(new MaintenanceType())
            ->setMileage(1200)
            ->setStartedAt($startedAt)
            ->setFinishedAt($finishedAt)
            ->setPlannedAt($plannedAt)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setIsExternal(true)
            ->setNotes('Done')
            ->setNextDueMileage(1500)
            ->setNextDueAt($nextDueAt)
            ->setIsDeleted(true);

        self::assertInstanceOf(Vehicle::class, $maintenance->getVehicle());
        self::assertInstanceOf(MaintenanceType::class, $maintenance->getMaintenanceType());
        self::assertSame(1200, $maintenance->getMileage());
        self::assertSame($startedAt, $maintenance->getStartedAt());
        self::assertSame($finishedAt, $maintenance->getFinishedAt());
        self::assertSame($plannedAt, $maintenance->getPlannedAt());
        self::assertSame(MaintenanceStatusEnum::Completed, $maintenance->getStatus());
        self::assertTrue($maintenance->isExternal());
        self::assertSame('Done', $maintenance->getNotes());
        self::assertSame(1500, $maintenance->getNextDueMileage());
        self::assertSame($nextDueAt, $maintenance->getNextDueAt());
        self::assertTrue($maintenance->isDeleted());

        $maintenance->onPrePersist();

        self::assertInstanceOf(\DateTimeImmutable::class, $maintenance->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $maintenance->getUpdatedAt());

        $createdAt = new \DateTimeImmutable('-2 days');
        $updatedAt = new \DateTimeImmutable('-1 day');
        $maintenance->setCreatedAt($createdAt)->setUpdatedAt($updatedAt);
        self::assertSame($createdAt, $maintenance->getCreatedAt());
        self::assertSame($updatedAt, $maintenance->getUpdatedAt());

        $maintenance->onPreUpdate();
        self::assertNotSame($updatedAt, $maintenance->getUpdatedAt());
    }

    public function testMaintenancePartRelationIsSetOnAdd(): void
    {
        $maintenance = new Maintenance();
        $maintenancePart = new MaintenancePart();

        $maintenance->addMaintenancePart($maintenancePart);
        $maintenance->addMaintenancePart($maintenancePart);

        self::assertTrue($maintenance->getMaintenanceParts()->contains($maintenancePart));
        self::assertCount(1, $maintenance->getMaintenanceParts());
        self::assertSame($maintenance, $maintenancePart->getMaintenance());

        $maintenance->removeMaintenancePart($maintenancePart);
        self::assertFalse($maintenance->getMaintenanceParts()->contains($maintenancePart));
        self::assertNull($maintenancePart->getMaintenance());
    }

    public function testRemovingDocumentSoftDeletesIt(): void
    {
        $maintenance = new Maintenance();
        $document = new Document();

        $maintenance->addDocument($document);
        $maintenance->addDocument($document);
        self::assertCount(1, $maintenance->getDocuments());
        $maintenance->removeDocument($document);

        self::assertTrue($document->isDeleted());

        $maintenance->removeDocument($document);
        self::assertTrue($document->isDeleted());
    }

    public function testBusinessDatesMustStayInAllowedRange(): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate((new Maintenance())
                ->setStartedAt(new \DateTimeImmutable('1799-12-31 23:59:59'))
                ->setFinishedAt(new \DateTimeImmutable('2101-01-01'))
                ->setPlannedAt(new \DateTimeImmutable('1799-12-31'))
                ->setNextDueAt(new \DateTimeImmutable('2101-01-01')));

        self::assertCount(4, $violations);
        self::assertSame(['startedAt', 'finishedAt', 'plannedAt', 'nextDueAt'], array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array($violations)));
    }

    public function testMileagesCannotBeNegative(): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate((new Maintenance())->setMileage(-1)->setNextDueMileage(-1));

        self::assertCount(2, $violations);
        self::assertSame(['mileage', 'nextDueMileage'], array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array($violations)));
    }
}
