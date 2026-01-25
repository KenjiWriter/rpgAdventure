<?php

namespace App\Enums;

enum ItemSlot: string
{
    case HEAD = 'head';
    case CHEST = 'chest';
    case LEGS = 'legs';
    case BOOTS = 'boots';
    case GLOVES = 'gloves';
    case MAIN_HAND = 'main_hand';
    case OFF_HAND = 'off_hand';
    case AMULET = 'amulet';
    case RING = 'ring';

    // Backpack and Warehouse are treated as context containers, 
    // but individual slots usually are just integers (1-42, 1-200).
    // However, if we need to denote the "location" type:
    // These might not be needed in the slot_id column if we use null or int, 
    // but for "moving" logic they are useful.
    // For the database 'slot_id', we will store either the enum value (for equipment) 
    // or the index (for backpack/warehouse). 
    // Ideally, for backpack/warehouse we might use 'backpack_1', 'warehouse_2' 
    // OR just an integer and rely on the polymorphic owner to distinguish (User=Warehouse, Character=Backpack).
    // Let's stick to the prompt: "slot_id (nullable for backpack/warehouse, specific string for equipment slots)"
    // Actually the prompt said: "slot_id (nullable for backpack/warehouse, specific string for equipment slots like 'chest')"
    // So for equipment, we use these values.
}
