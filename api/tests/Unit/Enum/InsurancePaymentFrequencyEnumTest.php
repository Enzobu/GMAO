<?php

namespace App\Tests\Unit\Enum;

use App\Enum\InsurancePaymentFrequencyEnum;
use PHPUnit\Framework\TestCase;

final class InsurancePaymentFrequencyEnumTest extends TestCase
{
    public function testCasesExposeExpectedValues(): void
    {
        self::assertSame(['monthly', 'yearly'], array_map(static fn (InsurancePaymentFrequencyEnum $case): string => $case->value, InsurancePaymentFrequencyEnum::cases()));
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (InsurancePaymentFrequencyEnum::cases() as $case) {
            self::assertNotSame('', $case->label());
        }
    }
}
