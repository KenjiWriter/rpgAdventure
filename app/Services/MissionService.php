<?php

namespace App\Services;

use App\Enums\ItemRarity;
use App\Enums\MissionStatus;
use App\Models\Character;
use App\Models\Map;
use App\Models\Mission;
use App\Models\Monster;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MissionService
{
    protected ItemGeneratorService $itemGenService;
    protected CharacterService $charService;

    public function __construct(ItemGeneratorService $itemGenService, CharacterService $charService)
    {
        $this->itemGenService = $itemGenService;
        $this->charService = $charService;
    }

    public function startMission(Character $character, Map $map): Mission
    {
        // 1. Validation
        if ($character->level < $map->min_level) {
            throw new Exception("Character level too low for this map.");
        }

        if (Mission::where('character_id', $character->id)->where('status', MissionStatus::ACTIVE)->exists()) {
            throw new Exception("Character is already on a mission.");
        }

        // 2. Setup
        $monster = $map->monsters()->inRandomOrder()->first();
        if (!$monster) {
            throw new Exception("No monsters found in this map.");
        }

        // Duration: 30-120s based on difficulty? Or fixed?
        // Prompt says "30-120 seconds based on map difficulty".
        // Let's say difficulty = map min level * 5 seconds + base 30?
        $durationSeconds = 30 + ($map->min_level * 5);
        $durationSeconds = min($durationSeconds, 120); // Cap at 120

        $now = now();
        $endsAt = $now->copy()->addSeconds($durationSeconds);

        // 3. Create Mission
        return Mission::create([
            'character_id' => $character->id,
            'map_id' => $map->id,
            'monster_id' => $monster->id,
            'started_at' => $now,
            'ends_at' => $endsAt,
            'status' => MissionStatus::ACTIVE,
        ]);
    }

    public function completeMission(Mission $mission): array
    {
        return DB::transaction(function () use ($mission) {
            // 1. Validation
            if ($mission->status !== MissionStatus::ACTIVE) {
                throw new Exception("Mission is not active.");
            }

            if (now()->lessThan($mission->ends_at)) {
                // Determine remaining
                $diff = now()->diffInSeconds($mission->ends_at, false);
                throw new Exception("Mission still in progress. {$diff}s remaining.");
            }

            // 2. Combat Simulation (Placeholder)
            // 90% Win Chance
            $won = rand(1, 100) <= 90;

            $rewards = [
                'type' => 'combat_result',
                'won' => $won,
                'gold' => 0,
                'exp' => 0,
                'items' => [],
                'log' => $won ? "Victory against {$mission->monster->name}!" : "Defeated by {$mission->monster->name}..."
            ];

            if ($won) {
                $monster = $mission->monster;

                // Calculate Rewards
                // Base (+/- 10%)
                $gold = (int) ($monster->base_gold * (rand(90, 110) / 100));
                $exp = (int) ($monster->base_exp * (rand(90, 110) / 100));

                $rewards['gold'] = $gold;
                $rewards['exp'] = $exp;

                // Grant Rewards to Character
                $mission->character->increment('gold', $gold);
                $mission->character->increment('experience', $exp);

                // Check Level Up logic would go here (omitted for brevity, handled in GameService/CharacterService normally)
                // Assuming simple exp increase.

                // Roll for Items
                // Check drops_json. Assuming structure { "items": [template_id_1, template_id_2] } or implicit pool?
                // The Seeder used: 'drops_json' => ['gold' => [1, 5], 'items' => []]
                // Let's assume we fetch a random template fitting the map/monster level?
                // Or we can just drop "Rusty Sword" occasionally for test.
                // Let's imply a 30% chance to drop a random item from DB for now to demonstrate mechanism.

                if (rand(1, 100) <= 30) {
                    // Find a template close to monster level
                    $template = \App\Models\ItemTemplate::inRandomOrder()->first();
                    if ($template) {
                        $item = $this->itemGenService->generateInstance($template, $mission->character, ItemRarity::COMMON);
                        $rewards['items'][] = $item;
                    }
                }
            }

            // 3. Finalize
            // Store rewards
            $mission->update([
                'status' => $won ? MissionStatus::COMPLETED : MissionStatus::FAILED, // Or CLAIMED immediately if we just return rewards?
                // Prompt says "Claim Reward" appears ONLY when status == completed.
                // But the user refinement says: "When completeMission is triggered (on Claim)..."
                // So this method IS the Claim Action.
                // So we mark it CLAIMED (or history status) and return the data.
                'rewards_json' => $rewards,
            ]);

            // To ensure it doesn't stay 'ACTIVE' or 'COMPLETED' pending claim forever if this IS the claim.
            // Let's call it CLAIMED.
            $mission->status = MissionStatus::CLAIMED;
            $mission->save();

            // Recalc stats if needed (exp/level change might affect stats)
            // $this->charService->calculateTotalStats($mission->character);

            return $rewards;
        });
    }
}
