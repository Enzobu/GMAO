<?php

namespace App\Tests\Unit;

use App\Kernel;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    public function testClassIsLoadable(): void
    {
        self::assertTrue(class_exists(Kernel::class) || enum_exists(Kernel::class));
    }
}
