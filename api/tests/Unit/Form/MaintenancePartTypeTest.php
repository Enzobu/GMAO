<?php

namespace App\Tests\Unit\Form;

use App\Entity\Part;
use App\Entity\PartType;
use App\Entity\Vehicle;
use App\Form\MaintenancePartType;
use Symfony\Component\Form\FormBuilderInterface;

final class MaintenancePartTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new MaintenancePartType(), []);
    }

    public function testPartChoiceLabelUsesPartTypeAndVehicleNames(): void
    {
        $capturedOptions = null;
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(static function (string $name, ?string $type = null, array $options = []) use (&$capturedOptions, $builder): FormBuilderInterface {
            if ($name === 'part') {
                $capturedOptions = $options;
            }

            return $builder;
        });

        (new MaintenancePartType())->buildForm($builder, []);

        $partType = (new PartType())->setName('Filtre');
        $vehicle = (new Vehicle())->setName('camion');
        $part = (new Part())->setPartType($partType)->addVehicle($vehicle);

        self::assertIsArray($capturedOptions);
        self::assertSame('Filtre (Camion)', $capturedOptions['choice_label']($part));
    }
}
