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
            ['name' => 'Starving Wolf', 'hp' => 25, 'min' => 2, 'max' => 5, 'spd' => 14, 'exp' => 15, 'gold' => 8, 'common_loot' => 0.2],
            ['name' => 'Field Slime', 'hp' => 45, 'min' => 1, 'max' => 3, 'spd' => 8, 'exp' => 12, 'gold' => 5, 'common_loot' => 0.3],
            ['name' => 'Angry Scarecrow', 'hp' => 35, 'min' => 3, 'max' => 6, 'spd' => 10, 'exp' => 18, 'gold' => 10, 'common_loot' => 0.2],
            ['name' => 'Giant Locust', 'hp' => 15, 'min' => 4, 'max' => 7, 'spd' => 18, 'exp' => 14, 'gold' => 6, 'common_loot' => 0.1],
            ['name' => 'Sickly Rat', 'hp' => 20, 'min' => 2, 'max' => 4, 'spd' => 12, 'exp' => 10, 'gold' => 4, 'common_loot' => 0.2],
            ['name' => 'Possessed Hoe', 'hp' => 30, 'min' => 3, 'max' => 5, 'spd' => 9, 'exp' => 16, 'gold' => 9, 'common_loot' => 0.25],
            ['name' => 'Wild Boar', 'hp' => 50, 'min' => 4, 'max' => 6, 'spd' => 7, 'exp' => 25, 'gold' => 15, 'rare_loot' => 0.05],
            ['name' => 'Lost Sheep', 'hp' => 25, 'min' => 1, 'max' => 2, 'spd' => 8, 'exp' => 20, 'gold' => 2, 'common_loot' => 0.4], // Bonus exp pinata
            ['name' => 'Thorn Bush', 'hp' => 40, 'min' => 2, 'max' => 3, 'spd' => 5, 'exp' => 15, 'gold' => 5, 'common_loot' => 0.2],
            ['name' => 'Crazed Farmer', 'hp' => 55, 'min' => 5, 'max' => 8, 'spd' => 11, 'exp' => 35, 'gold' => 25, 'uncommon_loot' => 0.1], // Mini Boss
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
        $starters = [
            [
                'name' => 'Rusty Shortsword',
                'type' => ItemType::WEAPON,
                'base' => ['min_dmg' => 2, 'max_dmg' => 4, 'strength' => 2],
                'lvl' => 1,
                'class' => CharacterClass::WARRIOR->value
            ],
            [
                'name' => 'Old Leather Boots',
                'type' => ItemType::ARMOR,
                'base' => ['defense' => 1, 'dexterity' => 1], // Boots usually provide defense too
                'lvl' => 1,
                'class' => null // Generic? Or Assassin? Prompt says "Starter Loot Set", implying drops.
            ],
            [
                'name' => 'Cracked Buckler',
                'type' => ItemType::ARMOR, // Offhand is armor? Or Weapon? Usually Armor.
                'base' => ['defense' => 3, 'vitality' => 2],
                'lvl' => 1,
                'class' => CharacterClass::WARRIOR->value
            ],
            [
                'name' => 'Faded Robe',
                'type' => ItemType::ARMOR,
                'base' => ['defense' => 1, 'intelligence' => 2],
                'lvl' => 1,
                'class' => CharacterClass::MAGE->value
            ],
            // Keep existing starters if needed or update them
            // The prompt asks for these specifically as "Drops". 
            // ItemTemplate is the source for both starters and drops.
        ];

        foreach ($starters as $s) {
            ItemTemplate::updateOrCreate(
                ['name' => $s['name']],
                [
                    'type' => $s['type'],
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
