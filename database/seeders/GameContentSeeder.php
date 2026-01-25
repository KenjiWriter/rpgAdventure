<?php

namespace Database\Seeders;

use App\Enums\CharacterClass;
use App\Enums\ItemType;
use App\Models\ItemTemplate;
use App\Models\Map;
use App\Models\Monster;
use Illuminate\Database\Seeder;

class GameContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Core Maps
        $field = Map::create(['name' => 'Whispering Fields', 'min_level' => 1]);
        $peak = Map::create(['name' => 'Shadow Peak', 'min_level' => 6]);

        // 2. Monsters
        Monster::create([
            'map_id' => $field->id,
            'name' => 'Rabid Dog',
            'hp' => 50,
            'min_dmg' => 3,
            'max_dmg' => 6,
            'speed' => 10,
            'element' => 'earth',
            'drops_json' => ['gold' => [1, 5], 'items' => []]
        ]);

        Monster::create([
            'map_id' => $peak->id,
            'name' => 'Shadow Wolf',
            'hp' => 120,
            'min_dmg' => 8,
            'max_dmg' => 12,
            'speed' => 12,
            'element' => 'wind',
            'drops_json' => ['gold' => [10, 20]]
        ]);

        // 3. Starter Items
        // Warrior
        ItemTemplate::create([
            'name' => 'Rusty Sword',
            'type' => ItemType::WEAPON,
            'base_stats' => ['min_dmg' => 2, 'max_dmg' => 4],
            'min_level' => 1,
            'class_restriction' => CharacterClass::WARRIOR->value,
        ]);

        ItemTemplate::create([
            'name' => 'Worn Tunic',
            'type' => ItemType::ARMOR,
            'base_stats' => ['vitality' => 1, 'defense' => 2],
            'min_level' => 1,
            'class_restriction' => CharacterClass::WARRIOR->value,
        ]);

        // Assassin
        ItemTemplate::create([
            'name' => 'Chipped Dagger',
            'type' => ItemType::WEAPON,
            'base_stats' => ['min_dmg' => 1, 'max_dmg' => 3, 'speed_bonus' => 5],
            'min_level' => 1,
            'class_restriction' => CharacterClass::ASSASSIN->value,
        ]);

        // Mage
        ItemTemplate::create([
            'name' => 'Bent Staff',
            'type' => ItemType::WEAPON,
            'base_stats' => ['intelligence' => 2, 'min_dmg' => 1, 'max_dmg' => 3],
            'min_level' => 1,
            'class_restriction' => CharacterClass::MAGE->value,
        ]);

        // Materials
        ItemTemplate::create([
            'name' => 'Upgrade Stone',
            'type' => ItemType::MATERIAL,
            'base_stats' => [],
            'min_level' => 1,
        ]);
    }
}
