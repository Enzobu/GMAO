<?php

namespace App\Enum;

enum VehicleTypeEnum: string
{
    case Car = 'car';
    case Motorcycle = 'motorcycle';
    case Utility = 'utility';
    case Truck = 'truck';
    case Van = 'van';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Car => 'Voiture',
            self::Motorcycle => 'Moto',
            self::Utility => 'Utilitaire',
            self::Truck => 'Camion',
            self::Van => 'Fourgon',
            self::Other => 'Autre',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Car => 'primary',
            self::Motorcycle => 'success',
            self::Utility => 'warning',
            self::Truck => 'warning',
            self::Van => 'info',
            self::Other => 'danger',
        };
    }
}
