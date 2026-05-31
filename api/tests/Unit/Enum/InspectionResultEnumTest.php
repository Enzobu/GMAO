<?php

namespace App\Tests\Unit\Enum;

use App\Enum\InspectionResultEnum;
use PHPUnit\Framework\TestCase;

final class InspectionResultEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['pass', 'counter_visit', 'fail'], array_map(static fn (InspectionResultEnum $case): string => $case->value, InspectionResultEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (InspectionResultEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }
}
