<?php

namespace App\Tests\Unit\Form;

use App\Entity\User;
use App\Enum\VehicleColorEnum;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use App\Form\VehicleType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class VehicleTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method("isGranted")->with("ROLE_ADMIN")->willReturn(false);

        $this->assertFormTypeBuildsAndConfigures(new VehicleType($auth), ["edit" => false]);
    }

    public function testChoiceClosuresAndAdminUserField(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $captured = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(static function (string $name, ?string $type = null, array $options = []) use (&$captured, $builder): FormBuilderInterface {
            $captured[$name] = $options;

            return $builder;
        });

        (new VehicleType($auth))->buildForm($builder, ['edit' => true]);

        self::assertFalse($captured['lastMileage']['attr']['disabled']);
        self::assertArrayHasKey('user', $captured);
        self::assertSame('Camion', $captured['type']['choice_label'](VehicleTypeEnum::Truck));
        self::assertSame(VehicleTypeEnum::Truck->value, $captured['type']['choice_value'](VehicleTypeEnum::Truck));
        self::assertSame('', $captured['type']['choice_value'](null));
        self::assertSame('Diesel', $captured['fuelType']['choice_label'](VehicleFuelTypeEnum::Diesel));
        self::assertSame(VehicleFuelTypeEnum::Diesel->value, $captured['fuelType']['choice_value'](VehicleFuelTypeEnum::Diesel));
        self::assertSame('Manuelle', $captured['transmission']['choice_label'](VehicleTransmissionTypeEnum::Manual));
        self::assertSame(VehicleTransmissionTypeEnum::Manual->value, $captured['transmission']['choice_value'](VehicleTransmissionTypeEnum::Manual));
        self::assertSame('Blanc', $captured['color']['choice_label'](VehicleColorEnum::white));
        self::assertSame(VehicleColorEnum::white->value, $captured['color']['choice_value'](VehicleColorEnum::white));
        self::assertSame('Hors service', $captured['status']['choice_label'](VehicleStatusEnum::OutOfService));
        self::assertSame(VehicleStatusEnum::OutOfService->value, $captured['status']['choice_value'](VehicleStatusEnum::OutOfService));
        self::assertSame('jean dupont - jd@example.com', $captured['user']['choice_label']((new User())->setFirstname('Jean')->setLastname('Dupont')->setEmail('jd@example.com')));
    }
}
