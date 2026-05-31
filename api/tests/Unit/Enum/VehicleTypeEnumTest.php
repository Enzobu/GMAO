<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleTypeEnum;
use PHPUnit\Framework\TestCase;

final class VehicleTypeEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['car', 'motorcycle', 'utility', 'truck', 'van', 'other'], array_map(static fn (VehicleTypeEnum $case): string => $case->value, VehicleTypeEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (VehicleTypeEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }

    public function testEveryCaseHasAVariant(): void
    {
        self::assertSame(['primary', 'success', 'warning', 'warning', 'info', 'danger'], array_map(static fn (VehicleTypeEnum $case): string => $case->variant(), VehicleTypeEnum::cases()));
    }
}
