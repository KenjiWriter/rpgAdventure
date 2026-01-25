<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quests = [
            [
                'title' => 'Into the Wild',
                'description' => 'The local population of Wild Dogs has grown too large. Reduce their numbers.',
                'objective_type' => 'kill_monster',
                'objective_target' => '1', // Assuming Monster ID 1 is Wild Dog/Wolf
                // If ID is not 1, we might need to find it, but for seeder hardcoding or lookup is fine.
                // Better: find a monster.
                'objective_count' => 5,
                'reward_gold' => 100,
                'reward_xp' => 50,
            ],
            [
                'title' => 'Rat Problem',
                'description' => 'Clear the sewers of Giant Rats.',
                'objective_type' => 'kill_monster',
                'objective_target' => '2', // Assuming ID 2
                'objective_count' => 3,
                'reward_gold' => 50,
                'reward_xp' => 25,
            ]
        ];

        foreach ($quests as $data) {
            // Lookup monster if needed, or create if missing?
            // Assuming GameContentSeeder ran and there are monsters.
            // Let's rely on flexible target matching or just generic check.
            // Since we use ID in MissionService, we need valid IDs.
            // Let's match by name if possible?
            // "objective_target" in DB is string.
            // MissionService uses $monster->id.
            // So we need to put the ID in 'objective_target'.

            // Hack for MVP: Get random monster ID
            $monster = \App\Models\Monster::inRandomOrder()->first();
            if ($monster) {
                $data['objective_target'] = (string) $monster->id;
                $data['title'] .= " ({$monster->name})"; // Append name to valid title
            }

            $quest = \App\Models\Quest::create($data);

            // Assign to all existing characters
            $characters = \App\Models\Character::all();
            foreach ($characters as $character) {
                \App\Models\CharacterQuest::firstOrCreate([
                    'character_id' => $character->id,
                    'quest_id' => $quest->id,
                ]);
            }
        }
    }
}
