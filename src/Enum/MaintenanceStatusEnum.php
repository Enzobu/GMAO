<?php

namespace App\Enum;

enum MaintenanceStatusEnum: string
{
    case ToDo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ToDo => 'À faire',
            self::InProgress => 'En cours',
            self::Completed => 'Terminé',
            self::Cancelled => 'Abandonné',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::ToDo => 'danger',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'dark',
        };
    }
}