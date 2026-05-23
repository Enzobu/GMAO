<?php

namespace App\Enum;

enum InspectionResultEnum: string
{
    case Pass = 'pass';
    case CounterVisit = 'counter_visit';
    case Fail = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Favorable',
            self::CounterVisit => 'Contre-visite',
            self::Fail => 'Défavorable',
        };
    }
}