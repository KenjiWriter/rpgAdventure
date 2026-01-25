<?php

namespace App\Services;

use App\Enums\ItemRarity;
use App\Models\Character;
use App\Models\ItemTemplate;
use App\Models\MerchantItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MerchantService
{
    protected ItemGeneratorService $itemGen;
    protected CharacterService $charService;

    public function __construct(ItemGeneratorService $itemGen, CharacterService $charService)
    {
        $this->itemGen = $itemGen;
        $this->charService = $charService;
    }

    public function getStock(Character $character): array
    {
        // cleanup expired
        // Actually, if expired, we should refresh automatically? "Refresh: Timer 60-minutowy"
        // If expired, generate new.
        $exists = MerchantItem::where('character_id', $character->id)
            ->where('expires_at', '>', now())
            ->exists();

        if (!$exists) {
            $this->generateStock($character);
        }

        return MerchantItem::where('character_id', $character->id)
            ->with('template')
            ->orderBy('slot_index')
            ->get()
            ->toArray();
    }

    public function refreshStockManual(Character $character): array
    {
        $cost = $character->level * 10;
        if ($character->gold < $cost) {
            throw new Exception("Not enough gold. Need {$cost}g.");
        }

        return DB::transaction(function () use ($character, $cost) {
            $character->decrement('gold', $cost);
            return $this->generateStock($character);
        });
    }

    public function generateStock(Character $character): array
    {
        // Clear old stock
        MerchantItem::where('character_id', $character->id)->delete();

        $generated = [];
        $expiresAt = now()->addMinutes(60);

        for ($i = 0; $i < 6; $i++) {
            $rarity = $this->rollRarity();
            // Find template close to level. +/- 2 levels? or just <= level + 1
            $template = ItemTemplate::where('min_level', '<=', $character->level + 2)
                ->inRandomOrder()
                ->first();

            if (!$template)
                continue;

            // Generate "Ghost" Stats using service, but dont create DB record yet
            // We need to slightly refactor ItemGenerator or just simulate it.
            // ItemGenerator creates INSTANCE. We want array data.
            // Let's create a temporary instance, extract data, then rollback? No that's messy.
            // Let's use ItemGeneratorService provided it creates Model but we don't save it? 
            // The service uses ::create(). 
            // We should use the internal logic. 
            // For MVP: We will use the service to generate a REAL instance, convert to JSON, then DELETE the instance.
            // This is inefficient but safe. Or we refactor service to separate "make" from "save".
            // Let's just create an instance owned by 'merchant_stock' (polymorphic trick) or temporary.
            // Actually, we can just save the bonuses array? 
            // ItemGeneratorService::generateInstance returns ItemInstance.
            // We can wrap in transaction and rollback, but we want the DATA.

            // Hacky but works: Create instance, grabbing bonuses, delete instance.
            // Optimization: Refactor ItemGeneratorService to have a 'rollBonuses' public method.
            // Since we don't have permission to refactor heavily without verifying, let's assume we can add a helper or duplicate logic.
            // Actually, looking at ItemGeneratorService (Step 1493), it has `rollSlotsCount` and `rollBonus` as private.
            // We can make them public or just copy logic? 
            // Better: Add `public function previewInstance(template, rarity)` to service.

            // For now, I will add `previewInstance` to ItemGeneratorService in next step.
            // Assuming it exists:
            $preview = $this->itemGen->previewInstanceAttributes($template, $rarity);

            // Calculate Price
            // Base * Level * RarityMult
            $basePrice = 10;
            $price = (int) ($basePrice * $template->min_level * $rarity->multiplier() * rand(0.8, 1.2));

            $item = MerchantItem::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'character_id' => $character->id,
                'item_template_id' => $template->id,
                'cost' => max(1, $price),
                'slot_index' => $i,
                'data' => [
                    'rarity' => $rarity->value,
                    'bonuses' => $preview['bonuses'],
                    'upgrade_level' => 0
                ],
                'expires_at' => $expiresAt
            ]);

            $generated[] = $item;
        }

        return $generated;
    }

    public function buyItem(Character $character, string $merchantItemId): void
    {
        DB::transaction(function () use ($character, $merchantItemId) {
            $offer = MerchantItem::where('id', $merchantItemId)
                ->where('character_id', $character->id)
                ->lockForUpdate()
                ->first();

            if (!$offer)
                throw new Exception("Item not found or expired.");
            if ($character->gold < $offer->cost)
                throw new Exception("Not enough gold.");

            // Deduct Gold
            $character->decrement('gold', $offer->cost);

            // Create Real Item
            // We need to force specific bonuses.
            // ItemGeneratorService doesn't allow forcing bonuses easily? 
            // We should just manually create ItemInstance here since we have the data.
            \App\Models\ItemInstance::create([
                'item_template_id' => $offer->item_template_id,
                'owner_id' => $character->id,
                'owner_type' => Character::class,
                'slot_id' => $this->charService->findFreeSlot($character), // Helpers needed
                'upgrade_level' => $offer->data['upgrade_level'] ?? 0,
                'bonuses' => $offer->data['bonuses'] ?? [],
            ]);

            // Remove offer
            $offer->delete();

            // Log
            $this->charService->logActivity($character, 'system', "Bought {$offer->template->name} for {$offer->cost}g.");
        });
    }

    private function rollRarity(): ItemRarity
    {
        $rand = rand(1, 100);
        if ($rand <= 50)
            return ItemRarity::COMMON;
        if ($rand <= 80)
            return ItemRarity::UNCOMMON;
        if ($rand <= 95)
            return ItemRarity::RARE;
        if ($rand <= 99)
            return ItemRarity::EPIC;
        return ItemRarity::LEGENDARY;
    }
}
