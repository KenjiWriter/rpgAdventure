<?php

namespace App\Enums;

enum ItemType: string
{
    case WEAPON = 'weapon';
    case ARMOR = 'armor';
    case MATERIAL = 'material';
    case CONSUMABLE = 'consumable';
}
