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
        $field = Map::updateOrCreate(
            ['name' => 'Whispering Fields'],
            ['min_level' => 1]
        );
        $forest = Map::updateOrCreate(
            ['name' => 'Dark Forest'],
            ['min_level' => 5]
        );
        $mine = Map::updateOrCreate(
            ['name' => 'Abandoned Mine'],
            ['min_level' => 10]
        );

        // 2. Monsters
        // Field
        Monster::updateOrCreate([
            'map_id' => $field->id,
            'name' => 'Rabid Dog',
        ], [
            'hp' => 50,
            'min_dmg' => 3,
            'max_dmg' => 6,
            'speed' => 10,
            'element' => 'earth',
            'base_gold' => 5,
            'base_exp' => 10,
            'drops_json' => ['common_loot' => 0.5]
        ]);

        // Forest
        Monster::updateOrCreate([
            'map_id' => $forest->id,
            'name' => 'Shadow Wolf',
        ], [
            'hp' => 120,
            'min_dmg' => 8,
            'max_dmg' => 12,
            'speed' => 12,
            'element' => 'wind',
            'base_gold' => 15,
            'base_exp' => 25,
            'drops_json' => ['rare_loot' => 0.1]
        ]);

        // Mine
        Monster::updateOrCreate([
            'map_id' => $mine->id,
            'name' => 'Cave Troll',
        ], [
            'hp' => 300,
            'min_dmg' => 20,
            'max_dmg' => 30,
            'speed' => 5,
            'element' => 'earth',
            'base_gold' => 40,
            'base_exp' => 60,
            'drops_json' => ['gem' => 0.05]
        ]);

        // 3. Starter Items
        // Warrior
        ItemTemplate::updateOrCreate([
            'name' => 'Rusty Sword',
        ], [
            'type' => ItemType::WEAPON,
            'base_stats' => ['min_dmg' => 2, 'max_dmg' => 4],
            'min_level' => 1,
            'class_restriction' => CharacterClass::WARRIOR->value,
        ]);

        ItemTemplate::updateOrCreate([
            'name' => 'Worn Tunic',
        ], [
            'type' => ItemType::ARMOR,
            'base_stats' => ['vitality' => 1, 'defense' => 2],
            'min_level' => 1,
            'class_restriction' => CharacterClass::WARRIOR->value,
        ]);

        // Assassin
        ItemTemplate::updateOrCreate([
            'name' => 'Chipped Dagger',
        ], [
            'type' => ItemType::WEAPON,
            'base_stats' => ['min_dmg' => 1, 'max_dmg' => 3, 'speed_bonus' => 5],
            'min_level' => 1,
            'class_restriction' => CharacterClass::ASSASSIN->value,
        ]);

        // Mage
        ItemTemplate::updateOrCreate([
            'name' => 'Bent Staff',
        ], [
            'type' => ItemType::WEAPON,
            'base_stats' => ['intelligence' => 2, 'min_dmg' => 1, 'max_dmg' => 3],
            'min_level' => 1,
            'class_restriction' => CharacterClass::MAGE->value,
        ]);

        // Materials
        ItemTemplate::updateOrCreate([
            'name' => 'Upgrade Stone',
        ], [
            'type' => ItemType::MATERIAL,
            'base_stats' => [],
            'min_level' => 1,
        ]);
    }
}
