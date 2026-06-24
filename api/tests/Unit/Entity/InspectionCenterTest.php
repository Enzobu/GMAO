<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use App\Entity\InspectionCenter;
use App\Entity\VehicleInspection;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Validation;

final class InspectionCenterTest extends TestCase
{
    public function testAccessorsAndInspectionRelation(): void
    {
        $address = new Address();
        $emptyCenter = new InspectionCenter();
        self::assertNull($emptyCenter->getId());
        self::assertFalse($emptyCenter->isDeleted());

        $center = (new InspectionCenter())
            ->setName('Controle technique')
            ->setPhone('0102030405')
            ->setEmail('ct@example.com')
            ->setAddress($address)
            ->setIsDeleted(true);

        self::assertSame('Controle technique', $center->getName());
        self::assertSame('01 02 03 04 05', $center->getPhone());
        self::assertSame('ct@example.com', $center->getEmail());
        self::assertSame($address, $center->getAddress());
        self::assertTrue($center->isDeleted());

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

    public function testNameMustBeUnique(): void
    {
        $uniqueEntityAttributes = (new \ReflectionClass(InspectionCenter::class))
            ->getAttributes(UniqueEntity::class);

        self::assertCount(1, $uniqueEntityAttributes);
        $attribute = $uniqueEntityAttributes[0]->newInstance();

        self::assertSame(['name'], $attribute->fields);
        self::assertSame(
            'Ce centre de contrôle technique existe déjà.',
            $attribute->message,
        );
    }

    public function testPhoneMustUseFrenchGroupedFormat(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        self::assertCount(0, $validator->validateProperty(
            (new InspectionCenter())->setPhone('0485748596'),
            'phone',
        ));

        $violations = $validator->validateProperty(
            (new InspectionCenter())->setPhone('00 85 74 85 96'),
            'phone',
        );

        self::assertCount(1, $violations);
        self::assertSame('phone', $violations[0]->getPropertyPath());
        self::assertSame('Le téléphone doit respecter le format 04 85 74 85 96.', $violations[0]->getMessage());
        self::assertSame('123', (new InspectionCenter())->setPhone(' 123 ')->getPhone());
        self::assertNull((new InspectionCenter())->setPhone(' ')->getPhone());
    }
}
