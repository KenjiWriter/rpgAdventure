<?php

namespace App\Enums;

enum ItemRarity: string
{
    case COMMON = 'common';
    case RARE = 'rare';
    case EPIC = 'epic';
    case LEGENDARY = 'legendary';

    public function multiplier(): float
    {
        return match ($this) {
            self::COMMON => 1.0,
            self::RARE => 1.2,
            self::EPIC => 1.5,
            self::LEGENDARY => 2.0,
        };
    }
}
