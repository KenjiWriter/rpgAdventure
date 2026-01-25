<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Models\Character;
use App\Models\ItemInstance;
use App\Models\ItemTemplate;
use Exception;
use Illuminate\Support\Facades\DB;

class UpgradeService
{
    protected CharacterService $characterService;

    public function __construct(CharacterService $charService)
    {
        $this->characterService = $charService;
    }

    public function refine(ItemInstance $item): array
    {
        // 1. Validation
        if (!$this->isUpgradable($item)) {
            throw new Exception("This item cannot be upgraded.");
        }

        $character = $item->owner;
        if (!($character instanceof Character)) {
            throw new Exception("Item must be in character inventory to upgrade.");
        }

        // 2. Calculate Costs
        $currentLevel = $item->upgrade_level;
        $goldCost = 10 + ($currentLevel * 10);
        $materialCost = $currentLevel + 1; // 1 stone for +1, 2 stones for +2...
        $materialName = 'Upgrade Stone';

        // 3. Transaction
        return DB::transaction(function () use ($item, $character, $goldCost, $materialCost, $materialName) {

            // Check Gold
            if ($character->gold < $goldCost) {
                throw new Exception("Not enough gold. Required: {$goldCost}");
            }

            // Check Materials (Stackable Logic)
            // Ideally we find all instances of 'Upgrade Stone' in backpack and deduct quantity.
            // Current ItemInstance schema doesn't have 'quantity'.
            // Assuming 1 instance = 1 item rule for now based on Schema (no quantity column in migration).
            // So we need to delete X instances of 'Upgrade Stone'.

            // Find material templates
            $matTemplate = ItemTemplate::where('name', $materialName)->firstOrFail();

            // Find instances owned by character
            $materials = ItemInstance::where('owner_id', $character->id)
                ->where('owner_type', Character::class)
                ->where('item_template_id', $matTemplate->id)
                ->limit($materialCost)
                ->get();

            if ($materials->count() < $materialCost) {
                throw new Exception("Not enough materials. Required: {$materialCost} {$materialName}(s)");
            }

            // Consume Resources
            $character->decrement('gold', $goldCost);

            // Delete materials
            // In a real stackable system we would decrement quantity. 
            // Here we delete rows.
            ItemInstance::destroy($materials->pluck('id'));

            // Upgrade Item
            $item->increment('upgrade_level');
            $item->refresh();

            // Recalculate Stats if Equipped
            // If the item is in an equipment slot
            $isEquipped = in_array($item->slot_id, array_column(\App\Enums\ItemSlot::cases(), 'value'));
            if ($isEquipped) {
                // Return new total stats
                $newStats = $this->characterService->calculateTotalStats($character);
            } else {
                $newStats = $character->stats->computed_stats;
            }

            return [
                'success' => true,
                'new_level' => $item->upgrade_level,
                'remaining_gold' => $character->gold,
                'new_stats' => $newStats,
                'message' => "Upgrade successful! +10% Base Stats."
            ];
        });
    }

    private function isUpgradable(ItemInstance $item): bool
    {
        // Only weapons and armor
        return in_array($item->template->type, [ItemType::WEAPON, ItemType::ARMOR]);
    }
}
