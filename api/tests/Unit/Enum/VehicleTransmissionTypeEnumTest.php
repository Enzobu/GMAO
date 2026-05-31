<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleTransmissionTypeEnum;
use PHPUnit\Framework\TestCase;

final class VehicleTransmissionTypeEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['manual', 'automatic', 'cvt', 'semi_automatic', 'dual_clutch', 'other'], array_map(static fn (VehicleTransmissionTypeEnum $case): string => $case->value, VehicleTransmissionTypeEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (VehicleTransmissionTypeEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }

    public function testEveryCaseHasAVariant(): void
    {
        self::assertSame(['success', 'danger', 'primary', 'warning', 'danger', 'info'], array_map(static fn (VehicleTransmissionTypeEnum $case): string => $case->variant(), VehicleTransmissionTypeEnum::cases()));
    }
}
