<?php

namespace App\Tests\Unit\Form;

use App\Form\ResetPasswordRequestFormType;

final class ResetPasswordRequestFormTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new ResetPasswordRequestFormType(), []);
    }
}
