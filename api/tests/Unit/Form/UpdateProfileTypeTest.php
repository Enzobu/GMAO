<?php

namespace App\Tests\Unit\Form;

use App\Form\UpdateProfileType;

final class UpdateProfileTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new UpdateProfileType(), []);
    }
}
