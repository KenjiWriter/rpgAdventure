<?php

namespace App\Services;

use App\Enums\ItemRarity;
use App\Enums\ItemType;
use App\Models\ItemInstance;
use App\Models\ItemTemplate;
use Illuminate\Database\Eloquent\Model;

class ItemGeneratorService
{
    public function generateInstance(ItemTemplate $template, Model $owner, ItemRarity $rarity): ItemInstance
    {
        // 1. Determine Number of Slots (Weighted)
        $slotsCount = $this->rollSlotsCount($rarity);

        // 2. Generate Bonuses
        $bonuses = [];
        for ($i = 0; $i < $slotsCount; $i++) {
            $bonuses[] = $this->rollBonus($template, $rarity);
        }

        // 3. Create Instance
        // We assume 'owner' is handled by caller or defaults to backpack logic.
        // For new items, usually slot_id is null (backpack) or determined by InventoryService.
        // But here we just create it. 
        // We'll set slot_id to null (backpack) for now implicitly. Or let defaults handle it.
        // We'll assume the owner is a Character or User (Warehouse).
        // To properly put it in a free slot, we should use InventoryService or just let it float and frontend sorts it.
        // But the schema expects slot_id string. We'll verify what schema allows.
        // Schema: "slot_id (nullable for backpack/warehouse)" -> perfect.

        return ItemInstance::create([
            'item_template_id' => $template->id,
            'owner_id' => $owner->id,
            'owner_type' => get_class($owner),
            'upgrade_level' => 0,
            'bonuses' => $bonuses,
        ]);
    }

    private function rollSlotsCount(ItemRarity $rarity): int
    {
        // Customizable Logic
        return match ($rarity) {
            ItemRarity::COMMON => 1, // 1 slot
            ItemRarity::RARE => rand(1, 10) > 8 ? 3 : 2, // 2 slots guaranteed, 20% for 3
            ItemRarity::EPIC => rand(1, 10) > 8 ? 4 : 3, // 3 slots guaranteed, 20% for 4
            ItemRarity::LEGENDARY => rand(1, 10) > 5 ? 5 : 4, // 5 slots (50%), 4 guaranteed
        };
    }

    private function rollBonus(ItemTemplate $template, ItemRarity $itemRarity): array
    {
        // Definitions
        $weaponBonuses = ['min_dmg', 'max_dmg', 'strength', 'dexterity', 'intelligence', 'speed_bonus'];
        $armorBonuses = ['vitality', 'defense', 'resistance_fire', 'resistance_water', 'resistance_earth', 'resistance_wind'];

        $pool = $template->type === ItemType::WEAPON ? $weaponBonuses : $armorBonuses;

        $type = $pool[array_rand($pool)];

        // Individual Roll Rarity (Small chance to exceed item rarity)
        $rollRarity = $itemRarity; // Default to item rarity
        // maybe small upgrade logic here? e.g. Legendary stats on Epic item? keeping simple for now.

        // Value Calculation
        // Base value * Level scaling * Rarity
        // Example: Base 1 * Lvl 1 * 1.0 = 1
        // Example: Base 1 * Lvl 10 * 2.0 = 20
        $baseValue = 1;
        // Customize base value per type? Vitality usually higher than Str.
        if (in_array($type, ['vitality', 'hp']))
            $baseValue = 2;
        if (in_array($type, ['defense']))
            $baseValue = 3;

        $level = $template->min_level;
        $value = (int) ceil($baseValue * $level * $rollRarity->multiplier() * (rand(80, 120) / 100)); // +/- 20% variance

        return [
            'type' => $type,
            'value' => max(1, $value),
            'rarity' => $rollRarity->value
        ];
    }
}
