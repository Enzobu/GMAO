<?php

namespace App\Tests\Unit\Form;

use App\Form\MaintenanceTypeFormType;

final class MaintenanceTypeFormTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new MaintenanceTypeFormType(), []);
    }
}
