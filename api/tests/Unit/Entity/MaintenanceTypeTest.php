<?php

namespace App\Tests\Unit\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Maintenance;
use App\Entity\MaintenanceType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

final class MaintenanceTypeTest extends TestCase
{
    public function testAccessorsAndSoftDeleteFlag(): void
    {
        $type = (new MaintenanceType())
            ->setName('Vidange')
            ->setDescription('Entretien moteur')
            ->setIsDeleted(true);

        self::assertSame('Vidange', $type->getName());
        self::assertSame('Entretien moteur', $type->getDescription());
        self::assertTrue($type->isDeleted());
    }

    public function testNameMustBeUnique(): void
    {
        $uniqueEntityAttributes = (new \ReflectionClass(MaintenanceType::class))
            ->getAttributes(UniqueEntity::class);

        self::assertCount(1, $uniqueEntityAttributes);
        $attribute = $uniqueEntityAttributes[0]->newInstance();

        self::assertSame(['name'], $attribute->fields);
        self::assertSame('Ce type d’entretien existe déjà.', $attribute->message);
    }

    public function testCollectionIsNotPaginated(): void
    {
        $resourceAttributes = (new \ReflectionClass(MaintenanceType::class))
            ->getAttributes(ApiResource::class);

        self::assertCount(1, $resourceAttributes);

        foreach ($resourceAttributes[0]->newInstance()->getOperations() as $operation) {
            if ($operation instanceof GetCollection) {
                self::assertFalse($operation->getPaginationEnabled());

                return;
            }
        }

        self::fail('GetCollection operation not found.');
    }

    public function testMaintenanceRelationIsBidirectional(): void
    {
        $type = new MaintenanceType();
        $maintenance = new Maintenance();

        $type->addMaintenance($maintenance);

        self::assertTrue($type->getMaintenances()->contains($maintenance));
        self::assertSame($type, $maintenance->getMaintenanceType());

        $type->removeMaintenance($maintenance);

        self::assertFalse($type->getMaintenances()->contains($maintenance));
        self::assertNull($maintenance->getMaintenanceType());
    }
}
