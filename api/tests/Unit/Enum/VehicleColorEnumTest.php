<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleColorEnum;
use PHPUnit\Framework\TestCase;

final class VehicleColorEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['red', 'green', 'blue', 'pink', 'purple', 'violet', 'orange', 'yellow', 'cyan', 'gray', 'black', 'white'], array_map(static fn (VehicleColorEnum $case): string => $case->value, VehicleColorEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (VehicleColorEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }

    public function testEveryCaseHasAVariant(): void
    {
        self::assertSame(['danger', 'success', 'blue', 'pink', 'purple', 'violet', 'orange', 'yellow', 'cyan', 'gray', 'black', 'white'], array_map(static fn (VehicleColorEnum $case): string => $case->variant(), VehicleColorEnum::cases()));
    }
}
