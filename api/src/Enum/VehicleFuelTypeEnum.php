<?php

namespace App\Enum;

enum VehicleFuelTypeEnum: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Ethanol = 'ethanol';
    case Hybrid = 'hybrid';
    case Electric = 'electric';
    case LPG = 'lpg';
    case CNG = 'cng';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Petrol => 'Essence',
            self::Diesel => 'Diesel',
            self::Ethanol => 'Éthanol (E85)',
            self::Hybrid => 'Hybride',
            self::Electric => 'Électrique',
            self::LPG => 'GPL',
            self::CNG => 'GNV',
            self::Other => 'Autre',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Petrol => 'primary',
            self::Diesel => 'danger',
            self::Ethanol => 'success',
            self::Hybrid => 'secondary',
            self::Electric => 'warning',
            self::LPG => 'info',
            self::CNG => 'danger',
            self::Other => 'tertiary',
        };
    }
}