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
        // 2. Monsters
        // 2a. Whispering Fields (Level 1-3)
        $fieldsMonsters = [
            ['name' => 'Starving Wolf', 'hp' => 15, 'min' => 2, 'max' => 5, 'spd' => 14, 'exp' => 15, 'gold' => 8, 'common_loot' => 0.2], // 25 -> 15
            ['name' => 'Field Slime', 'hp' => 30, 'min' => 1, 'max' => 3, 'spd' => 8, 'exp' => 12, 'gold' => 5, 'common_loot' => 0.3], // 45 -> 30
            ['name' => 'Angry Scarecrow', 'hp' => 25, 'min' => 3, 'max' => 6, 'spd' => 10, 'exp' => 18, 'gold' => 10, 'common_loot' => 0.2], // 35 -> 25
            ['name' => 'Giant Locust', 'hp' => 10, 'min' => 4, 'max' => 7, 'spd' => 18, 'exp' => 14, 'gold' => 6, 'common_loot' => 0.1], // 15 -> 10
            ['name' => 'Sickly Rat', 'hp' => 10, 'min' => 2, 'max' => 4, 'spd' => 12, 'exp' => 10, 'gold' => 4, 'common_loot' => 0.2], // 20 -> 10
            ['name' => 'Possessed Hoe', 'hp' => 20, 'min' => 3, 'max' => 5, 'spd' => 9, 'exp' => 16, 'gold' => 9, 'common_loot' => 0.25], // 30 -> 20
            ['name' => 'Wild Boar', 'hp' => 35, 'min' => 4, 'max' => 6, 'spd' => 7, 'exp' => 25, 'gold' => 15, 'rare_loot' => 0.05], // 50 -> 35
            ['name' => 'Lost Sheep', 'hp' => 15, 'min' => 1, 'max' => 2, 'spd' => 8, 'exp' => 20, 'gold' => 2, 'common_loot' => 0.4], // 25 -> 15
            ['name' => 'Thorn Bush', 'hp' => 25, 'min' => 2, 'max' => 3, 'spd' => 5, 'exp' => 15, 'gold' => 5, 'common_loot' => 0.2], // 40 -> 25
            ['name' => 'Crazed Farmer', 'hp' => 40, 'min' => 5, 'max' => 8, 'spd' => 11, 'exp' => 35, 'gold' => 25, 'uncommon_loot' => 0.1], // 55 -> 40
        ];

        foreach ($fieldsMonsters as $m) {
            Monster::updateOrCreate(
                ['name' => $m['name'], 'map_id' => $field->id],
                [
                    'hp' => $m['hp'],
                    'min_dmg' => $m['min'],
                    'max_dmg' => $m['max'],
                    'speed' => $m['spd'],
                    'base_gold' => $m['gold'],
                    'base_exp' => $m['exp'],
                    'drops_json' => ['common_loot' => $m['common_loot'] ?? 0.2]
                ]
            );
        }

        // Forest & Mine (Keep existing or update strictly if needed, leaving as is for now for brevity, 
        // effectively cleaning up the individual calls above for Field)
        Monster::updateOrCreate([
            'map_id' => $forest->id,
            'name' => 'Shadow Wolf',
        ], [
            'hp' => 80,
            'min_dmg' => 5,
            'max_dmg' => 8,
            'speed' => 12,
            'base_gold' => 15,
            'base_exp' => 25
        ]);

        Monster::updateOrCreate([
            'map_id' => $mine->id,
            'name' => 'Cave Troll',
        ], [
            'hp' => 200,
            'min_dmg' => 15,
            'max_dmg' => 25,
            'speed' => 5,
            'base_gold' => 40,
            'base_exp' => 60
        ]);

        // 3. Starter Items
        // Re-seeding with new Base Stats schema
        $starters = [
            [
                'name' => 'Rusty Shortsword',
                'type' => ItemType::WEAPON,
                'damage_min' => 5,
                'damage_max' => 8,
                'defense' => 0,
                'base' => ['strength' => 2],
                'lvl' => 1,
                'class' => CharacterClass::WARRIOR->value
            ],
            [
                'name' => 'Rusty Dagger',
                'type' => ItemType::WEAPON,
                'damage_min' => 3,
                'damage_max' => 5,
                'defense' => 0,
                'base' => ['dexterity' => 1],
                'lvl' => 1,
                'class' => CharacterClass::ASSASSIN->value
            ],
            [
                'name' => 'Apprentice Staff',
                'type' => ItemType::WEAPON,
                'damage_min' => 8,
                'damage_max' => 12,
                'defense' => 0,
                'base' => ['intelligence' => 3],
                'lvl' => 1,
                'class' => CharacterClass::MAGE->value
            ],
            [
                'name' => 'Old Leather Tunic',
                'type' => ItemType::ARMOR,
                'damage_min' => 0,
                'damage_max' => 0,
                'defense' => 6,
                'base' => ['vitality' => 1],
                'lvl' => 1,
                'class' => null
            ],
            [
                'name' => 'Cracked Buckler',
                'type' => ItemType::ARMOR,
                'damage_min' => 1,
                'damage_max' => 2,
                'defense' => 4,
                'base' => ['vitality' => 2],
                'lvl' => 1,
                'class' => CharacterClass::WARRIOR->value
            ],
            [
                'name' => 'Faded Robe',
                'type' => ItemType::ARMOR,
                'damage_min' => 0,
                'damage_max' => 0,
                'defense' => 3,
                'base' => ['intelligence' => 2],
                'lvl' => 1,
                'class' => CharacterClass::MAGE->value
            ]
        ];

        foreach ($starters as $s) {
            ItemTemplate::updateOrCreate(
                ['name' => $s['name']],
                [
                    'type' => $s['type'],
                    'base_damage_min' => $s['damage_min'] ?? 0,
                    'base_damage_max' => $s['damage_max'] ?? 0,
                    'base_defense' => $s['defense'] ?? 0,
                    'base_stats' => $s['base'],
                    'min_level' => $s['lvl'],
                    'class_restriction' => $s['class']
                ]
            );
        }

        // Ensure original starters still exist if referenced elsewhere, 
        // but 'Rusty Sword' vs 'Rusty Shortsword' might be duplicate-ish. 
        // I'll update the 'Rusty Sword' to 'Rusty Shortsword' in my mind or just add these alongside.
        // Prompt says "Create item templates".

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
