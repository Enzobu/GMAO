<?php

namespace App\Enum;

enum MaintenanceTypeEnum: string
{
    case OIL_CHANGE = 'oil_change';
    case OIL_FILTER = 'oil_filter';
    case AIR_FILTER = 'air_filter';
    case CABIN_FILTER = 'cabin_filter';
    case FUEL_FILTER = 'fuel_filter';
    case SPARK_PLUGS = 'spark_plugs';
    case GLOW_PLUGS = 'glow_plugs';
    case BRAKE_PADS = 'brake_pads';
    case BRAKE_DISCS = 'brake_discs';
    case BRAKE_FLUID = 'brake_fluid';
    case COOLANT = 'coolant';
    case TIMING_BELT = 'timing_belt';
    case ACCESSORY_BELT = 'accessory_belt';
    case CLUTCH = 'clutch';
    case GEARBOX_OIL = 'gearbox_oil';
    case SUSPENSION = 'suspension';
    case BATTERY = 'battery';
    case TIRES = 'tires';
    case CHAIN_KIT = 'chain_kit';
    case TECHNICAL_INSPECTION_PREPARATION = 'technical_inspection_preparation';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OIL_CHANGE => 'Vidange',
            self::OIL_FILTER => 'Filtre à huile',
            self::AIR_FILTER => 'Filtre à air',
            self::CABIN_FILTER => 'Filtre d’habitacle',
            self::FUEL_FILTER => 'Filtre à carburant',
            self::SPARK_PLUGS => 'Bougies',
            self::GLOW_PLUGS => 'Bougies de préchauffage',
            self::BRAKE_PADS => 'Plaquettes de frein',
            self::BRAKE_DISCS => 'Disques de frein',
            self::BRAKE_FLUID => 'Liquide de frein',
            self::COOLANT => 'Liquide de refroidissement',
            self::TIMING_BELT => 'Courroie de distribution',
            self::ACCESSORY_BELT => 'Courroie accessoire',
            self::CLUTCH => 'Embrayage',
            self::GEARBOX_OIL => 'Huile de boîte',
            self::SUSPENSION => 'Suspension',
            self::BATTERY => 'Batterie',
            self::TIRES => 'Pneus',
            self::CHAIN_KIT => 'Kit chaîne',
            self::TECHNICAL_INSPECTION_PREPARATION => 'Préparation contrôle technique',
            self::OTHER => 'Autre',
        };
    }
}