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
    protected CombatEngine $combatEngine;
    protected MountService $mountService;

    public function __construct(
        ItemGeneratorService $itemGenService,
        CharacterService $charService,
        CombatEngine $combatEngine,
        MountService $mountService
    ) {
        $this->itemGenService = $itemGenService;
        $this->charService = $charService;
        $this->combatEngine = $combatEngine;
        $this->mountService = $mountService;
    }

    public function startMission(Character $character, Map $map): Mission
    {
        // 1. Validation
        if ($character->level < $map->min_level) {
            throw new Exception("Character level {$character->level} is too low for this map (Req: {$map->min_level}).");
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
        $baseDurationSeconds = 30 + ($map->min_level * 5);
        $baseDurationSeconds = min($baseDurationSeconds, 120); // Cap at 120

        // Apply Mount Reduction
        $activeMount = $this->mountService->getActiveMount($character);
        $durationSeconds = $this->mountService->calculateReducedDuration($baseDurationSeconds, $activeMount);

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

            // 2. Combat Simulation (Real Engine)
            $combatResult = $this->combatEngine->simulate($mission->character, $mission->monster);
            $won = $combatResult['is_victory'];

            $rewards = [
                'type' => 'combat_result',
                'won' => $won,
                'gold' => 0,
                'exp' => 0,
                'items' => [],
                'combat_log' => $combatResult['log'],
                'seed' => $combatResult['seed'],
                'final_hp' => $combatResult['final_hp']
            ];

            if ($won) {
                // Award Logic remains same
                $monster = $mission->monster;

                // Log Victory
                $this->charService->logActivity($mission->character, 'combat', "Defeated {$monster->name}!");

                // Update Quests (Kill Monster)
                $this->updateQuestProgress($mission->character, 'kill_monster', $monster->id);

                // Calculate Rewards
                // Base (+/- 10%)
                $gold = (int) ($monster->base_gold * (rand(90, 110) / 100));
                $exp = (int) ($monster->base_exp * (rand(90, 110) / 100));

                $rewards['gold'] = $gold;
                $rewards['exp'] = $exp;

                // Grant Rewards to Character
                $mission->character->increment('gold', $gold);
                $mission->character->increment('experience', $exp);

                // Roll for Items
                // 30% Chance
                if (rand(1, 100) <= 30) {
                    // Find a template close to monster level
                    $template = \App\Models\ItemTemplate::inRandomOrder()->first();
                    if ($template) {
                        $item = $this->itemGenService->generateInstance($template, $mission->character, ItemRarity::COMMON);

                        // Assign Slot
                        $occupied = $mission->character->items()->pluck('slot_id')->toArray();
                        $freeSlot = null;
                        $maxSlots = $mission->character->inventory_slots ?? 30;
                        for ($i = 1; $i <= $maxSlots; $i++) {
                            if (!in_array("backpack_$i", $occupied)) {
                                $freeSlot = "backpack_$i";
                                break;
                            }
                        }

                        if ($freeSlot) {
                            $item->update(['slot_id' => $freeSlot]);
                        } else {
                            // Inventory Full - Item Lost or handle logic?
                            // For this MVP, we might just let it be null (invisible) but log it?
                            // Or simpler: Just don't assign slot, user has to organize?
                            // But prompt says "Ensure items are visible".
                            // If full, we can't make it visible in grid.
                            // We'll leave it as is if full.
                        }

                        $rewards['items'][] = $item;

                        // Log Loot with Name and Stats Snapshot
                        $itemName = $item->template->name ?? 'Unknown Item';
                        $statsSnapshot = [
                            'rarity' => $item->template->rarity->value ?? 'common',
                            'bonuses' => $item->bonuses
                        ];

                        $this->charService->logActivity(
                            $mission->character,
                            'loot',
                            "Found {$itemName}!",
                            [
                                'item_id' => $item->id,
                                'item_name' => $itemName,
                                'stats' => $statsSnapshot
                            ]
                        );
                    }
                }
            } else {
                $this->charService->logActivity($mission->character, 'combat', "Defeated by {$mission->monster->name}.");
            }

            // 3. Finalize
            // Store rewards
            $mission->update([
                'status' => $won ? MissionStatus::COMPLETED : MissionStatus::FAILED,
                'rewards_json' => $rewards,
            ]);

            $mission->status = MissionStatus::CLAIMED;
            $mission->save();

            return $rewards;
        });
    }

    protected function updateQuestProgress(Character $character, string $type, string $target): void
    {
        // Find active quests for this character that match the objective
        // We assume we have a pivot model CharacterQuest or we query DB directly
        // Using DB query for MVP or creating model relation. We have CharacterQuest model.

        $activeCharacterQuests = \App\Models\CharacterQuest::where('character_id', $character->id)
            ->where('is_completed', false)
            ->whereHas('quest', function ($q) use ($type, $target) {
                $q->where('objective_type', $type)
                    ->where('objective_target', $target);
            })
            ->with('quest')
            ->get();

        foreach ($activeCharacterQuests as $charQuest) {
            $charQuest->increment('progress');

            // Check completion
            if ($charQuest->progress >= $charQuest->quest->objective_count) {
                $charQuest->update(['is_completed' => true]);
                $this->charService->logActivity($character, 'system', "Quest Completed: {$charQuest->quest->title}!");
            }
        }
    }
}
