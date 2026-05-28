<?php

namespace App\Tests\Unit\Form;

use App\Form\PartTypeType;

final class PartTypeTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new PartTypeType(), []);
    }
}
