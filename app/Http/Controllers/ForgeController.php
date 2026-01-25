<?php

namespace App\Http\Controllers;

use App\Models\ItemInstance;
use App\Models\Character;
use App\Services\UpgradeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ForgeController extends Controller
{
    protected UpgradeService $upgradeService;

    public function __construct(UpgradeService $upgradeService)
    {
        $this->upgradeService = $upgradeService;
    }

    public function upgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|uuid|exists:item_instances,id',
        ]);

        $item = ItemInstance::findOrFail($validated['item_id']);

        // Auth Check
        // Item must belong to a character owned by the user
        $character = $item->owner;
        if (!($character instanceof Character) || $character->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->upgradeService->refine($item);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
