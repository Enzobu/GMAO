<?php

namespace App\Tests\Unit\Enum;

use App\Enum\MaintenanceStatusEnum;
use PHPUnit\Framework\TestCase;

final class MaintenanceStatusEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['todo', 'in_progress', 'completed', 'cancelled'], array_map(static fn (MaintenanceStatusEnum $case): string => $case->value, MaintenanceStatusEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (MaintenanceStatusEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }

    public function testEveryCaseHasAVariant(): void
    {
        self::assertSame([
            'danger',
            'warning',
            'success',
            'dark',
        ], array_map(static fn (MaintenanceStatusEnum $case): string => $case->variant(), MaintenanceStatusEnum::cases()));
    }
}
