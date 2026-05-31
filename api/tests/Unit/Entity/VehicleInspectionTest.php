<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\InspectionCenter;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Enum\InspectionResultEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class VehicleInspectionTest extends TestCase
{
    public function testAccessorsAndLifecycleTimestamps(): void
    {
        $date = new \DateTimeImmutable('2025-01-01');
        $validUntil = new \DateTimeImmutable('2027-01-01');
        $counterVisitDueAt = new \DateTimeImmutable('2025-02-01');
        self::assertNull((new VehicleInspection())->getId());

        $inspection = (new VehicleInspection())
            ->setVehicle(new Vehicle())
            ->setInspectionDate($date)
            ->setValidUntil($validUntil)
            ->setMileage(1200)
            ->setResult(InspectionResultEnum::Pass)
            ->setCounterVisitRequired(true)
            ->setCounterVisitDueAt($counterVisitDueAt)
            ->setCenter(new InspectionCenter())
            ->setNotes('OK')
            ->setIsDeleted(true);

        self::assertInstanceOf(Vehicle::class, $inspection->getVehicle());
        self::assertSame($date, $inspection->getInspectionDate());
        self::assertSame($validUntil, $inspection->getValidUntil());
        self::assertSame(1200, $inspection->getMileage());
        self::assertSame(InspectionResultEnum::Pass, $inspection->getResult());
        self::assertTrue($inspection->isCounterVisitRequired());
        self::assertSame($counterVisitDueAt, $inspection->getCounterVisitDueAt());
        self::assertInstanceOf(InspectionCenter::class, $inspection->getCenter());
        self::assertSame('OK', $inspection->getNotes());
        self::assertTrue($inspection->isDeleted());

        $inspection->onCreate();

        self::assertInstanceOf(\DateTimeImmutable::class, $inspection->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $inspection->getUpdatedAt());

        $createdAt = new \DateTimeImmutable('-2 days');
        $updatedAt = new \DateTimeImmutable('-1 day');
        $inspection->setCreatedAt($createdAt)->setUpdatedAt($updatedAt);
        self::assertSame($createdAt, $inspection->getCreatedAt());
        self::assertSame($updatedAt, $inspection->getUpdatedAt());

        $inspection->onUpdate();
        self::assertNotSame($updatedAt, $inspection->getUpdatedAt());
    }

    public function testRemovingDocumentSoftDeletesIt(): void
    {
        $inspection = new VehicleInspection();
        $document = new Document();

        $inspection->addDocument($document);
        $inspection->addDocument($document);
        self::assertCount(1, $inspection->getDocuments());
        $inspection->removeDocument($document);

        self::assertTrue($document->isDeleted());

        $inspection->removeDocument($document);
        self::assertTrue($document->isDeleted());
    }

    public function testValidationAddsViolationsForInvalidDatesAndMissingCounterVisitDueDate(): void
    {
        $inspection = (new VehicleInspection())
            ->setInspectionDate(new \DateTimeImmutable('2025-02-01'))
            ->setValidUntil(new \DateTimeImmutable('2025-01-01'))
            ->setResult(InspectionResultEnum::CounterVisit);
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::exactly(2))->method('atPath')->willReturnSelf();
        $builder->expects(self::exactly(2))->method('addViolation');
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::exactly(2))->method('buildViolation')->willReturn($builder);

        $inspection->validate($context);
    }

    public function testValidationAcceptsIncompleteDates(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        (new VehicleInspection())->validate($context);
    }

    public function testBusinessDatesMustStayInAllowedRange(): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate((new VehicleInspection())
                ->setInspectionDate(new \DateTimeImmutable('1799-12-31'))
                ->setValidUntil(new \DateTimeImmutable('2101-01-01'))
                ->setMileage(1200)
                ->setCounterVisitDueAt(new \DateTimeImmutable('2101-01-01')));

        self::assertCount(3, $violations);
        self::assertSame(['inspectionDate', 'validUntil', 'counterVisitDueAt'], array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array($violations)));
    }

    public function testMileageCannotBeNegative(): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate((new VehicleInspection())->setMileage(-1));

        self::assertCount(1, $violations);
        self::assertSame('mileage', $violations[0]->getPropertyPath());
        self::assertSame('Le kilométrage ne peut pas être négatif.', $violations[0]->getMessage());
    }

    public function testMileageIsRequired(): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate(new VehicleInspection());

        self::assertCount(1, $violations);
        self::assertSame('mileage', $violations[0]->getPropertyPath());
        self::assertSame('Le kilométrage est obligatoire.', $violations[0]->getMessage());
    }

    public function testCounterVisitIsDerivedFromResult(): void
    {
        $inspection = (new VehicleInspection())
            ->setResult(InspectionResultEnum::CounterVisit);

        self::assertTrue($inspection->isCounterVisitRequired());

        $inspection
            ->setCounterVisitDueAt(new \DateTimeImmutable('2025-02-01'))
            ->setResult(InspectionResultEnum::Pass);

        self::assertFalse($inspection->isCounterVisitRequired());
        self::assertNull($inspection->getCounterVisitDueAt());
    }

    public function testValidationRejectsCounterVisitDateForPassResult(): void
    {
        $inspection = (new VehicleInspection())
            ->setResult(InspectionResultEnum::Pass)
            ->setCounterVisitDueAt(new \DateTimeImmutable('2025-02-01'));
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('counterVisitDueAt')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->willReturn($builder);

        $inspection->validate($context);
    }
}
