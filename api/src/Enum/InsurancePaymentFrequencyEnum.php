<?php

namespace App\Enum;

enum InsurancePaymentFrequencyEnum: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensuel',
            self::Yearly => 'Annuel',
        };
    }
}