<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\ItemInstance;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request): JsonResponse
    {
        // Expects character_id for backpack, or defaults to user warehouse if not?
        // Prompt: "Returns the character's backpack items."
        $request->validate([
            'character_id' => 'required|uuid|exists:characters,id',
        ]);

        $character = Character::findOrFail($request->character_id);

        if ($character->user_id !== auth()->id()) {
            // return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Backpack items are items owned by Character where slot_id starts with 'backpack' OR just validation logic.
        // My definition: slot_id can be anything not in Equipment Enum for backpack?
        // Or strictly 'backpack_%'.
        // Let's assume we return ALL items owned by character that are NOT in equipment slots.

        $backpackItems = $character->items->filter(function ($item) {
            // Simple check: is it in equipment enum?
            $isEquipped = in_array($item->slot_id, array_column(\App\Enums\ItemSlot::cases(), 'value'));
            return !$isEquipped;
        })->values();

        return response()->json([
            'items' => $backpackItems
        ]);
    }

    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|uuid|exists:item_instances,id',
            'target_owner_type' => 'required|string|in:character,user', // simplified alias
            'target_owner_id' => 'required|uuid', // could be user id or character id
            'target_slot' => 'required|string',
        ]);

        $item = ItemInstance::findOrFail($validated['item_id']);

        // Resolve Target Owner
        // Security: Check if user owns the target.
        if ($validated['target_owner_type'] === 'user') {
            $targetOwner = User::findOrFail($validated['target_owner_id']);
            if ($targetOwner->id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            $targetOwner = Character::findOrFail($validated['target_owner_id']);
            if ($targetOwner->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        try {
            $movedItem = $this->inventoryService->moveItem(
                $item,
                $targetOwner,
                $validated['target_slot']
            );

            return response()->json([
                'message' => 'Item moved successfully',
                'item' => $movedItem
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
