<?php

namespace App\Tests\Unit\Form;

use App\Form\DocumentType;

final class DocumentTypeTest extends FormTypeTestCase
{
    public function testBuildFormAndConfigureOptions(): void
    {
        $this->assertFormTypeBuildsAndConfigures(new DocumentType(), ["edit" => false]);
    }
}
