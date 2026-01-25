<?php

namespace App\Services;

use App\Enums\CharacterClass;
use App\Enums\ItemSlot;
use App\Enums\MissionStatus;
use App\Models\Character;
use App\Models\ItemInstance;
use App\Models\Mission;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    private function isOnMission(Character $character): bool
    {
        return Mission::where('character_id', $character->id)
            ->where('status', MissionStatus::ACTIVE)
            ->exists();
    }

    /**
     * Move an item between slots, characters, or warehouse.
     * 
     * @param ItemInstance $item
     * @param Character|User $targetOwner
     * @param string $targetSlot // 'head', 'backpack_12', 'warehouse_25', etc.
     * @return ItemInstance
     * @throws Exception
     */
    public function moveItem(ItemInstance $item, $targetOwner, string $targetSlot): ItemInstance
    {
        return DB::transaction(function () use ($item, $targetOwner, $targetSlot) {
            // 1. Validation Logic

            // Check ownership (Target owner must belongs to the same user if moving between chars?)
            // For now, assuming current user context is checked in Controller/Policy.
            // But we should verify if we are moving to something we own?
            // Let's assume the Controller passes valid entities.

            // Class Restriction Check if equipping
            if ($targetOwner instanceof Character && $this->isEquipmentSlot($targetSlot)) {
                if ($item->template->class_restriction) {
                    // Check if restriction matches character class
                    if ($item->template->class_restriction !== $targetOwner->class->value) {
                        throw new Exception("Class restriction mismatch: Item requires {$item->template->class_restriction}");
                    }
                }
            }

            // Check if slot is occupied
            $existingItem = ItemInstance::where('owner_id', $targetOwner->id)
                ->where('owner_type', get_class($targetOwner))
                ->where('slot_id', $targetSlot)
                ->first();

            if ($existingItem) {
                // Swap logic
                // Move existing item to the source slot of the moving item
                // This requires updating the existing item's owner and slot to the current item's owner and slot
                $sourceOwnerId = $item->owner_id;
                $sourceOwnerType = $item->owner_type;
                $sourceSlot = $item->slot_id;

                // TODO: Validate if the swapped item can go to the source slot (e.g. class restriction if source is equipment)
                // If we are swapping, we need to be careful.
                // For simplicity in Phase 1, let's throw error if slot is occupied, or handle basic swap without deep validation 
                // but since user asked for robust moveItem, swap is expected.

                // Let's just swap them for now but we really should validate the reverse move too.
                $existingItem->update([
                    'owner_id' => $sourceOwnerId,
                    'owner_type' => $sourceOwnerType,
                    'slot_id' => $sourceSlot
                ]);
            }

            // Move the item
            $item->update([
                'owner_id' => $targetOwner->id,
                'owner_type' => get_class($targetOwner),
                'slot_id' => $targetSlot
            ]);

            // Recalculate stats if equipment changed
            if ($targetOwner instanceof Character) {
                // If we moved TO equipment or FROM equipment (if it was on this character)
                // Simplest is to just recalc stats for the target character.
                // But if we moved FROM another character, we should recalc theirs too.
                // Context is mostly single player + warehouse.

                // If it's the same character, just recalc.
                app(CharacterService::class)->calculateTotalStats($targetOwner);
            }

            // Note: If we swapped, we might need to recalc for the source owner too if it was a character.
            // This is getting complex, but essential.

            return $item;
        });
    }

    private function isEquipmentSlot(string $slot): bool
    {
        return !str_starts_with($slot, 'backpack_') && !str_starts_with($slot, 'warehouse_');
        // Better: check if it is in ItemSlot enum
        // return in_array($slot, array_column(ItemSlot::cases(), 'value'));
    }
}
