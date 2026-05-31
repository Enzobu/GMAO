<?php

namespace App\Tests\Unit\Form;

use App\Form\LoginType;

final class LoginTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new LoginType(), []);
    }
}
