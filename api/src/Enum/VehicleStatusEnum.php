<?php

namespace App\Enum;

enum VehicleStatusEnum: string
{
    case Active = 'active';
    case Sold = 'sold';
    case Archived = 'archived';
    case Inactive = 'inactive';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Sold => 'Vendu',
            self::Archived => 'Archivé',
            self::Inactive => 'Inactif',
            self::OutOfService => 'Hors service',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Sold => 'secondary',
            self::Archived => 'dark',
            self::Inactive => 'warning',
            self::OutOfService => 'danger',
        };
    }
}
