<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\CharacterQuest;
use Illuminate\Http\JsonResponse;
use App\Services\CharacterService;
use Illuminate\Support\Facades\DB;
use App\Services\ItemGeneratorService;

class QuestController extends Controller
{
    protected CharacterService $charService;

    public function __construct(CharacterService $charService)
    {
        $this->charService = $charService;
    }

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $character = $user->characters()->firstOrFail();


        // Let's just return all quests with their relationship to this character.
        $quests = Quest::with([
            'characterQuests' => function ($q) use ($character) {
                $q->where('character_id', $character->id);
            }
        ])->get()->map(function ($quest) {
            $cq = $quest->characterQuests->first();
            return [
                'id' => $quest->id,
                'title' => $quest->title,
                'description' => $quest->description,
                'objective' => $this->formatObjective($quest),
                'progress' => $cq ? $cq->progress : 0,
                'total' => $quest->objective_count,
                'completed' => $cq ? (bool) $cq->is_completed : false,
                'claimed' => $cq ? (bool) $cq->is_claimed : false,
            ];
        });

        return response()->json($quests);
    }

    public function claim(int $id): JsonResponse
    {
        $user = auth()->user();
        $character = $user->characters()->firstOrFail();

        return DB::transaction(function () use ($character, $id) {
            $cq = CharacterQuest::where('character_id', $character->id)
                ->where('quest_id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$cq->is_completed) {
                return response()->json(['error' => 'Quest not completed'], 400);
            }

            if ($cq->is_claimed) {
                return response()->json(['error' => 'Quest already claimed'], 400);
            }

            // Award
            $quest = $cq->quest;
            $character->increment('gold', $quest->reward_gold);
            $character->increment('experience', $quest->reward_xp);

            $cq->update(['is_claimed' => true]);

            $this->charService->logActivity($character, 'system', "Claimed reward for {$quest->title}: {$quest->reward_gold} Gold, {$quest->reward_xp} XP");

            return response()->json(['message' => 'Reward claimed', 'gold' => $character->gold, 'xp' => $character->experience]);
        });
    }

    private function formatObjective(Quest $quest): string
    {
        // Simple formatter
        if ($quest->objective_type === 'kill_monster') {
            // Retrieve monster name if possible, or just use ID for now if lazy
            // Ideally define monster name in Quest or fetch it.
            // For MVP: "Kill 5 Monsters"
            return "Kill {$quest->objective_count} Targets";
        }
        return "Complete Objective";
    }
}
