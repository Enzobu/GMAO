<?php

namespace App\Enum;

enum VehicleTransmissionTypeEnum: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
    case CVT = 'cvt';
    case SemiAutomatic = 'semi_automatic';
    case DualClutch = 'dual_clutch';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuelle',
            self::Automatic => 'Automatique',
            self::CVT => 'CVT',
            self::SemiAutomatic => 'Semi-automatique',
            self::DualClutch => 'Double embrayage',
            self::Other => 'Autre',
        };
    }
}