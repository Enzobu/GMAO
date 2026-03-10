<?php

namespace App\Enum;

enum VehicleColorEnum: string
{
    case Red = 'red';
    case Green = 'green';
    case Blue = 'blue';
    case Pink = 'pink';
    case Purple = 'purple';
    case Violet = 'violet';
    case Orange = 'orange';
    case Yellow = 'yellow';
    case Cyan = 'cyan';
    case Gray = 'gray';
    case Black = 'black';
    case white = 'white';

    public function label(): string
    {
        return match ($this) {
            self::Red => 'Rouge',
            self::Green => 'Vert',
            self::Blue => 'Bleu',
            self::Pink => 'Rose',
            self::Purple => 'Pourpre',
            self::Violet => 'Violet',
            self::Orange => 'Orange',
            self::Yellow => 'Jaune',
            self::Cyan => 'Cyan',
            self::Gray => 'Gris',
            self::Black => 'Noir',
            self::white => 'Blanc',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Red => 'danger',
            self::Green => 'success',
            self::Blue => 'blue',
            self::Pink => 'pink',
            self::Purple => 'purple',
            self::Violet => 'violet',
            self::Orange => 'orange',
            self::Yellow => 'yellow',
            self::Cyan => 'cyan',
            self::Gray => 'gray',
            self::Black => 'black',
            self::white => 'white',
        };
    }
}
