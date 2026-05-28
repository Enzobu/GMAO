<?php

namespace App\Tests\Unit\Form;

use App\Form\AddressType;

final class AddressTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new AddressType(), []);
    }
}
