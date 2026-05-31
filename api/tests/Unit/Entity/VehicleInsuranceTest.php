<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Enum\InsurancePaymentFrequencyEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class VehicleInsuranceTest extends TestCase
{
    public function testAccessorsActiveStatusAndLifecycle(): void
    {
        self::assertNull((new VehicleInsurance())->getId());
        self::assertTrue((new VehicleInsurance())->isActive());

        $vehicle = new Vehicle();
        $start = new \DateTimeImmutable('-1 day');
        $end = new \DateTimeImmutable('+1 day');

        $insurance = (new VehicleInsurance())
            ->setVehicle($vehicle)
            ->setProviderName('Provider')
            ->setPolicyNumber('POL123')
            ->setStartDate($start)
            ->setEndDate($end)
            ->setPaymentFrequency(InsurancePaymentFrequencyEnum::Yearly)
            ->setIsDeleted(true);

        self::assertSame($vehicle, $insurance->getVehicle());
        self::assertSame('Provider', $insurance->getProviderName());
        self::assertSame('POL123', $insurance->getPolicyNumber());
        self::assertSame($start, $insurance->getStartDate());
        self::assertSame($end, $insurance->getEndDate());
        self::assertSame(InsurancePaymentFrequencyEnum::Yearly, $insurance->getPaymentFrequency());
        self::assertTrue($insurance->isActive());
        self::assertTrue($insurance->isDeleted());

        $insurance->setEndDate(new \DateTimeImmutable('-1 day'));
        self::assertFalse($insurance->isActive());

        $insurance->setEndDate(null);
        self::assertTrue($insurance->isActive());

        $insurance->setIsActive(false);
        self::assertFalse($insurance->isActiveFromDb());

        $insurance->setIsActiveFromDb(true);
        self::assertTrue($insurance->isActiveFromDb());

        $insurance->setIsActiveFromDb(false);
        self::assertFalse($insurance->isActiveFromDb());

        $insurance->onCreate();
        self::assertInstanceOf(\DateTimeImmutable::class, $insurance->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $insurance->getUpdatedAt());

        $createdAt = new \DateTimeImmutable('-2 days');
        $updatedAt = new \DateTimeImmutable('-1 day');

        $insurance
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt);

        self::assertSame($createdAt, $insurance->getCreatedAt());
        self::assertSame($updatedAt, $insurance->getUpdatedAt());

        $insurance->onUpdate();

        self::assertNotSame($updatedAt, $insurance->getUpdatedAt());
    }

    public function testRemovingDocumentSoftDeletesIt(): void
    {
        $insurance = new VehicleInsurance();
        $document = new Document();

        $insurance->addDocument($document);
        $insurance->addDocument($document);

        self::assertCount(1, $insurance->getDocuments());

        $insurance->removeDocument($document);

        self::assertTrue($document->isDeleted());

        $insurance->removeDocument($document);

        self::assertTrue($document->isDeleted());
    }

    public function testBusinessDatesMustStayInAllowedRange(): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate((new VehicleInsurance())
                ->setStartDate(new \DateTimeImmutable('1799-12-31'))
                ->setEndDate(new \DateTimeImmutable('2101-01-01')));

        self::assertCount(2, $violations);
        self::assertSame(['startDate', 'endDate'], array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array($violations)));
    }
}
