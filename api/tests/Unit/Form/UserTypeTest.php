<?php

namespace App\Tests\Unit\Form;

use App\Form\UserType;

final class UserTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new UserType(), []);
    }
}
