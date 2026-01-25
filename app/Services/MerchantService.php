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

        // Price Multipliers
        $rarityMults = [
            'common' => 1,
            'uncommon' => 2,
            'rare' => 5,
            'epic' => 10,
            'legendary' => 50,
        ];

        for ($i = 0; $i < 6; $i++) {
            $rarity = $this->rollRarity();

            // Level Logic: +/- 5 levels, min 1
            $minLevel = max(1, $character->level - 5);
            $maxLevel = $character->level + 5;

            $template = ItemTemplate::whereBetween('min_level', [$minLevel, $maxLevel])
                ->inRandomOrder()
                ->first();

            if (!$template) {
                $template = ItemTemplate::where('min_level', '<=', $character->level)
                    ->orderByDesc('min_level')
                    ->first();
            }

            if (!$template)
                $template = ItemTemplate::inRandomOrder()->first();
            if (!$template)
                continue;

            $preview = $this->itemGen->previewInstanceAttributes($template, $rarity);

            // Pricing Logic: (ItemLevel * 10) * RarityMultiplier
            $itemLevel = $template->min_level;
            $priceMult = $rarityMults[$rarity->value] ?? 1;

            $finalCost = ($itemLevel * 10) * $priceMult;

            $item = MerchantItem::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'character_id' => $character->id,
                'item_template_id' => $template->id,
                'cost' => max(10, $finalCost),
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
            \App\Models\ItemInstance::create([
                'item_template_id' => $offer->item_template_id,
                'owner_id' => $character->id,
                'owner_type' => Character::class,
                'slot_id' => $this->charService->findFreeSlot($character),
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
