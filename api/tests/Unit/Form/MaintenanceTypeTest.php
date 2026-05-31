<?php

namespace App\Tests\Unit\Form;

use App\Entity\Maintenance;
use App\Entity\MaintenanceType as MaintenanceTypeEntity;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Form\MaintenanceType;
use App\Repository\MaintenanceTypeRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;

final class MaintenanceTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new MaintenanceType(), ["data" => null, "vehicle_locked" => false]);
    }

    public function testClosuresAndLockedVehicleBranch(): void
    {
        $captured = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(static function (string $name, ?string $type = null, array $options = []) use (&$captured, $builder): FormBuilderInterface {
            $captured[$name] = $options;

            return $builder;
        });
        $currentType = new MaintenanceTypeEntity();
        $maintenance = (new Maintenance())->setMaintenanceType($currentType);

        (new MaintenanceType())->buildForm($builder, ['data' => $maintenance, 'vehicle_locked' => false]);

        self::assertSame('Camion', $captured['vehicle']['choice_label']((new Vehicle())->setName('camion')));
        self::assertSame('Terminé', $captured['status']['choice_label'](MaintenanceStatusEnum::Completed));

        $repository = $this->createMock(MaintenanceTypeRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository->expects(self::once())->method('createQueryBuilder')->with('mt')->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())->method('andWhere')->with('mt.isDeleted = :isDeleted')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('setParameter')->with('isDeleted', false)->willReturnSelf();
        $queryBuilder->expects(self::once())->method('orderBy')->with('mt.name', 'ASC')->willReturnSelf();
        $queryBuilder->expects(self::never())->method('orWhere');

        self::assertSame($queryBuilder, $captured['maintenanceType']['query_builder']($repository));

        $currentTypeWithId = new MaintenanceTypeEntity();
        $reflection = new \ReflectionProperty(MaintenanceTypeEntity::class, 'id');
        $reflection->setValue($currentTypeWithId, 7);
        $maintenanceWithCurrentType = (new Maintenance())->setMaintenanceType($currentTypeWithId);
        $capturedWithCurrentType = [];
        $builderWithCurrentType = $this->createMock(FormBuilderInterface::class);
        $builderWithCurrentType->method('add')->willReturnCallback(static function (string $name, ?string $type = null, array $options = []) use (&$capturedWithCurrentType, $builderWithCurrentType): FormBuilderInterface {
            $capturedWithCurrentType[$name] = $options;

            return $builderWithCurrentType;
        });
        (new MaintenanceType())->buildForm($builderWithCurrentType, ['data' => $maintenanceWithCurrentType, 'vehicle_locked' => false]);

        $repositoryWithCurrentType = $this->createMock(MaintenanceTypeRepository::class);
        $queryBuilderWithCurrentType = $this->createMock(QueryBuilder::class);
        $repositoryWithCurrentType->method('createQueryBuilder')->willReturn($queryBuilderWithCurrentType);
        $queryBuilderWithCurrentType->method('andWhere')->willReturnSelf();
        $queryBuilderWithCurrentType->method('setParameter')->willReturnSelf();
        $queryBuilderWithCurrentType->method('orderBy')->willReturnSelf();
        $queryBuilderWithCurrentType->expects(self::once())->method('orWhere')->with('mt.id = :currentMaintenanceTypeId')->willReturnSelf();

        self::assertSame($queryBuilderWithCurrentType, $capturedWithCurrentType['maintenanceType']['query_builder']($repositoryWithCurrentType));

        $lockedBuilder = $this->createMock(FormBuilderInterface::class);
        $lockedBuilder->method('add')->willReturnCallback(static function (string $name) use ($lockedBuilder): FormBuilderInterface {
            self::assertNotSame('vehicle', $name);

            return $lockedBuilder;
        });
        (new MaintenanceType())->buildForm($lockedBuilder, ['data' => null, 'vehicle_locked' => true]);
    }
}
