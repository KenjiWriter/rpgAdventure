<?php

namespace App\Services;

use App\Enums\CharacterClass;
use App\Models\Character;
use App\Models\CharacterStats;
use App\Models\User;
use Illuminate\Support\Facades\DB;

    protected ItemGeneratorService $itemGenService;

    public function __construct(ItemGeneratorService $itemGenService)
    {
        $this->itemGenService = $itemGenService;
    }

    public function createCharacter(User $user, string $name, CharacterClass $class): Character
    {
        return DB::transaction(function () use ($user, $name, $class) {
            $character = Character::create([
                'user_id' => $user->id,
                'name' => $name,
                'class' => $class,
                'level' => 1,
                'experience' => 0,
                'gold' => 0,
                'stat_points' => 0,
            ]);

            // Default stats 
            CharacterStats::create([
                'character_id' => $character->id,
                'strength' => 5,
                'dexterity' => 5,
                'intelligence' => 5,
                'vitality' => 5,
            ]);

            // Starter Items
            $starterTemplates = \App\Models\ItemTemplate::where('min_level', 1)
                ->where(function($q) use ($class) {
                    $q->where('class_restriction', $class)
                      ->orWhereNull('class_restriction');
                })
                ->get();

            foreach ($starterTemplates as $template) {
                // Generate Basic (Common) instance
                $this->itemGenService->generateInstance($template, $character, \App\Enums\ItemRarity::COMMON);
            }

            // Initial calculation of total stats (includes items)
            $this->calculateTotalStats($character);

            return $character;
        });
    }

    public function calculateTotalStats(Character $character): array
    {
        // Start with base stats
        $baseStats = $character->stats;

        $totalStats = [
            'strength' => $baseStats->strength,
            'dexterity' => $baseStats->dexterity,
            'intelligence' => $baseStats->intelligence,
            'vitality' => $baseStats->vitality,
            'resistance_wind' => $baseStats->resistance_wind,
            'resistance_fire' => $baseStats->resistance_fire,
            'resistance_water' => $baseStats->resistance_water,
            'resistance_earth' => $baseStats->resistance_earth,
            // Derived stats
            'max_hp' => $baseStats->vitality * 10,
            'max_mana' => $baseStats->intelligence * 10,

            // Initial placeholder, will be overwritten after adding item stats + scaling
            'damage_min' => 0,
            'damage_max' => 0,
        ];

        // Iterate over equipped items
        $equippedItems = $character->items()
            ->whereIn('slot_id', array_column(\App\Enums\ItemSlot::cases(), 'value'))
            ->get();

        foreach ($equippedItems as $item) {
            // Apply item base stats
            // With Upgrade Scaling: +10% per level
            if ($item->template && $item->template->base_stats) {
                // Calculate Multiplier
                $multiplier = 1 + ($item->upgrade_level * 0.10); // 10% per level

                foreach ($item->template->base_stats as $key => $value) {
                    $upgradedValue = (int) ($value * $multiplier);

                    if (isset($totalStats[$key])) {
                        $totalStats[$key] += $upgradedValue;
                    } else {
                        $totalStats[$key] = $upgradedValue;
                    }
                }
            }

            // Apply item bonuses
            if ($item->bonuses) {
                foreach ($item->bonuses as $bonus) {
                    if (isset($bonus['type']) && isset($bonus['value'])) {
                        $key = $bonus['type'];
                        if (isset($totalStats[$key])) {
                            $totalStats[$key] += $bonus['value'];
                        } else {
                            $totalStats[$key] = $bonus['value'];
                        }
                    }
                }
            }
        }

        // Cache the computed stats

        // Final Damage Calculation based on Attributes
        // Formula: Base Weapon Damage + (Main Stat * 0.5)
        $mainStatValue = match ($character->class) {
            CharacterClass::WARRIOR => $totalStats['strength'],
            CharacterClass::ASSASSIN => $totalStats['dexterity'],
            CharacterClass::MAGE => $totalStats['intelligence'],
            default => $totalStats['strength'],
        };

        // Ensure keys exist from items or default to 0 (Unarmed)
        $weaponMin = $totalStats['damage_min'] ?? 1;
        $weaponMax = $totalStats['damage_max'] ?? 2;

        // Add Stat Bonus
        $statBonus = (int) ($mainStatValue * 0.5);

        $totalStats['damage_min'] = $weaponMin + $statBonus;
        $totalStats['damage_max'] = $weaponMax + $statBonus;

        $baseStats->update(['computed_stats' => $totalStats]);

        return $totalStats;
    }
}
