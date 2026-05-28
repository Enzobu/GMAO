<?php

namespace App\Tests\Unit\Form;

use App\Form\PartFormType;

final class PartFormTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new PartFormType(), []);
    }
}
