<?php

namespace App\Tests\Unit\Form;

use App\Form\VehicleInsuranceType;

final class VehicleInsuranceTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new VehicleInsuranceType(), []);
    }
}
