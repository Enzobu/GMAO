<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleFuelTypeEnum;
use PHPUnit\Framework\TestCase;

final class VehicleFuelTypeEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['petrol', 'diesel', 'ethanol', 'hybrid', 'electric', 'lpg', 'cng', 'other'], array_map(static fn (VehicleFuelTypeEnum $case): string => $case->value, VehicleFuelTypeEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (VehicleFuelTypeEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }

    public function testEveryCaseHasAVariant(): void
    {
        self::assertSame(['primary', 'danger', 'success', 'secondary', 'warning', 'info', 'danger', 'tertiary'], array_map(static fn (VehicleFuelTypeEnum $case): string => $case->variant(), VehicleFuelTypeEnum::cases()));
    }
}
