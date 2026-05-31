<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleStatusEnum;
use PHPUnit\Framework\TestCase;

final class VehicleStatusEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['active', 'sold', 'archived', 'inactive', 'out_of_service'], array_map(static fn (VehicleStatusEnum $case): string => $case->value, VehicleStatusEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (VehicleStatusEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }

    public function testEveryCaseHasAVariant(): void
    {
        self::assertSame(['success', 'secondary', 'dark', 'warning', 'danger'], array_map(static fn (VehicleStatusEnum $case): string => $case->variant(), VehicleStatusEnum::cases()));
    }
}
