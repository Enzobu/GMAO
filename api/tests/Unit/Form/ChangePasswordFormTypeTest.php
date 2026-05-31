<?php

namespace App\Tests\Unit\Form;

use App\Form\ChangePasswordFormType;

final class ChangePasswordFormTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new ChangePasswordFormType(), []);
    }
}
