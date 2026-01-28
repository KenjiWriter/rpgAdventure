<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\MountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MountController extends Controller
{
    protected MountService $mountService;

    public function __construct(MountService $mountService)
    {
        $this->mountService = $mountService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'mounts' => $this->mountService->getAvailableMounts()
        ]);
    }

    public function rent(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'mount_type' => 'required|string',
        ]);

        $character = Character::findOrFail($request->character_id);

        if ($character->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $mountSession = $this->mountService->rentMount($character, $request->mount_type);
            return response()->json([
                'message' => 'Mount rented successfully',
                'mount_session' => $mountSession,
                'gold' => $character->gold // Return updated gold
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function active(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
        ]);

        $character = Character::findOrFail($request->character_id);

        if ($character->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activeMount = $this->mountService->getActiveMount($character);

        return response()->json([
            'active_mount' => $activeMount,
            'mount_details' => $activeMount ? MountService::MOUNTS[$activeMount->mount_type] ?? null : null
        ]);
    }
}
