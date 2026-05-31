<?php

namespace App\Tests\Unit\Form;

use App\Entity\InspectionCenter;
use App\Enum\InspectionResultEnum;
use App\Form\VehicleInspectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class VehicleInspectionTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method("isGranted")->with("ROLE_ADMIN")->willReturn(false);

        $this->assertFormTypeBuildsAndConfigures(new VehicleInspectionType($auth), ["edit" => false]);
    }

    public function testChoiceClosuresAndAdminDisabledState(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $captured = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(static function (string $name, ?string $type = null, array $options = []) use (&$captured, $builder): FormBuilderInterface {
            $captured[$name] = $options;

            return $builder;
        });

        (new VehicleInspectionType($auth))->buildForm($builder, ['edit' => true]);

        self::assertFalse($captured['mileage']['attr']['disabled']);
        self::assertSame('Favorable', $captured['result']['choice_label'](InspectionResultEnum::Pass));
        self::assertSame(InspectionResultEnum::Pass->value, $captured['result']['choice_value'](InspectionResultEnum::Pass));
        self::assertSame('', $captured['result']['choice_value'](null));
        self::assertSame('Centre', $captured['center']['choice_label']((new InspectionCenter())->setName('Centre')));
        self::assertSame('Centre #', $captured['center']['choice_label'](new InspectionCenter()));
    }
}
