<?php

namespace App\Http\Controllers;

use App\Enums\MissionStatus;
use App\Models\Character;
use App\Models\Map;
use App\Models\Mission;
use App\Services\MissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MissionController extends Controller
{
    protected MissionService $missionService;

    public function __construct(MissionService $missionService)
    {
        $this->missionService = $missionService;
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_id' => 'required|uuid|exists:characters,id',
            'map_id' => 'required|integer|exists:maps,id',
        ]);

        $character = Character::findOrFail($validated['character_id']);
        if ($character->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $map = Map::findOrFail($validated['map_id']);

        try {
            $mission = $this->missionService->startMission($character, $map);
            return response()->json([
                'message' => 'Mission started',
                'mission' => $mission
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function claim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mission_id' => 'required|uuid|exists:missions,id',
        ]);

        $mission = Mission::with(['character', 'monster', 'map'])->findOrFail($validated['mission_id']);

        if ($mission->character->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $rewards = $this->missionService->completeMission($mission);
            return response()->json([
                'message' => 'Mission completed',
                'rewards' => $rewards,
                'monster' => $mission->monster, // Pass monster source of truth
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function active(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_id' => 'required|uuid',
        ]);

        // Find active mission
        $mission = Mission::where('character_id', $validated['character_id'])
            ->where('status', MissionStatus::ACTIVE)
            ->with(['map', 'monster']) // Eager load for UI
            ->first();

        // If no active, maybe find one that is "Completed" but not Claimed if we split steps?
        // But our service does Claim on Complete.
        // So we just return null or empty.

        return response()->json([
            'mission' => $mission, // null if none
            'server_time' => now(), // For sync
        ]);
    }
}
