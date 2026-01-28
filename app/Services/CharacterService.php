<?php

namespace App\Services;

use App\Enums\CharacterClass;
use App\Models\Character;
use App\Models\CharacterStats;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CharacterService
{

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
                ->where(function ($q) use ($class) {
                    $q->where('class_restriction', $class)
                        ->orWhereNull('class_restriction');
                })
                ->get();

            foreach ($starterTemplates as $template) {
                // Generate Basic (Common) instance
                $this->itemGenService->generateInstance($template, $character, \App\Enums\ItemRarity::COMMON);
            }

            $this->calculateTotalStats($character);

            $this->logActivity($character, 'system', 'Character created.', ['class' => $class->value]);

            return $character;
        });
    }

    public function calculateTotalStats(Character $character): array
    {
        // 1. Reset: Start with clean base stats from DB column or Hardcoded base? 
        // CharacterStats table has 'strength', 'dexterity' etc. which are the "Base Points" + "Allocated Points".
        // They should NOT include item bonuses yet.
        // Assuming $character->stats returns the model with base values.

        // Reload stats to ensure we have fresh base values if they were modified in memory?
        // Actually, $character->stats is the relation.
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
            // Derived stats base
            'max_hp' => $baseStats->vitality * 10,
            'max_mana' => $baseStats->intelligence * 10,

            'damage_min' => 0,
            'damage_max' => 0,

            // Explicitly initialize defense to avoid undefined key
            'defense' => 0
        ];

        // Initialize Attack Speed
        $totalStats['attack_speed'] = $baseStats->attack_speed ?? 1.0;
        $attackSpeedPercent = 0;

        // Iterate over equipped items
        $equippedItems = $character->items()
            ->whereIn('slot_id', array_column(\App\Enums\ItemSlot::cases(), 'value'))
            ->get();

        foreach ($equippedItems as $item) {
            if ($item->template) {
                $multiplier = 1 + ($item->upgrade_level * 0.10);

                // Base Damage
                if ($item->template->base_damage_min) {
                    $dmgMin = (int) ($item->template->base_damage_min * $multiplier);
                    $dmgMax = (int) (($item->template->base_damage_max ?: $item->template->base_damage_min) * $multiplier);

                    $totalStats['damage_min'] += $dmgMin;
                    $totalStats['damage_max'] += $dmgMax;
                }

                // Base Defense
                if ($item->template->base_defense) {
                    $totalStats['defense'] += (int) ($item->template->base_defense * $multiplier);
                }

                // Base Stats (JSON)
                if ($item->template->base_stats) {
                    foreach ($item->template->base_stats as $key => $value) {
                        $upgradedValue = $value * $multiplier; // Float for precision first

                        // Handle Special Stats
                        if ($key === 'attack_speed') {
                            $totalStats['attack_speed'] += $upgradedValue;
                        } elseif ($key === 'attack_speed_percent') {
                            $attackSpeedPercent += $upgradedValue;
                        } else {
                            if (!isset($totalStats[$key]))
                                $totalStats[$key] = 0;
                            $totalStats[$key] += (int) $upgradedValue;
                        }
                    }
                }
            }

            // Random Bonuses
            if ($item->bonuses) {
                foreach ($item->bonuses as $bonus) {
                    if (isset($bonus['type']) && isset($bonus['value'])) {
                        $key = $bonus['type'];

                        if ($key === 'attack_speed') {
                            $totalStats['attack_speed'] += $bonus['value'];
                        } elseif ($key === 'attack_speed_percent') {
                            $attackSpeedPercent += $bonus['value'];
                        } else {
                            if (!isset($totalStats[$key]))
                                $totalStats[$key] = 0;
                            $totalStats[$key] += $bonus['value'];
                        }
                    }
                }
            }
        }

        // Final Attack Speed Calculation
        // Formula: (Base + Flat) * (1 + Percent/100)
        // Ensure minimum 0.1 speed
        $totalStats['attack_speed'] = max(0.1, $totalStats['attack_speed'] * (1 + ($attackSpeedPercent / 100)));

        // Final Damage Calculation
        // Formula: (MainStat * 1.5) + WeaponDamage
        $mainStatValue = match ($character->class) {
            CharacterClass::WARRIOR => $totalStats['strength'],
            CharacterClass::ASSASSIN => $totalStats['dexterity'],
            CharacterClass::MAGE => $totalStats['intelligence'],
            default => $totalStats['strength'],
        };

        // Base Unarmed is 1-2. We add this TO the weapon damage.
        // If weapon damage is 0, they do 1-2 + StatBonus.
        // Prompt Formula: (Strength * 1.5) + Weapon_Damage
        // Note: StatBonus was previously 0.5. Now 1.5.
        $statBonus = (int) ($mainStatValue * 1.5);

        $totalStats['damage_min'] = max(1, $totalStats['damage_min'] + $statBonus);
        $totalStats['damage_max'] = max(2, $totalStats['damage_max'] + $statBonus);

        // Save computed stats
        // Update both JSON and the separate column for query performance
        $baseStats->update([
            'attack_speed' => $totalStats['attack_speed'],
            'computed_stats' => $totalStats
        ]);

        return $totalStats;
    }

    public function findFreeSlot(Character $character): ?string
    {
        $maxSlots = $character->inventory_slots ?? 30;
        $occupied = $character->items()->pluck('slot_id')->toArray();
        for ($i = 1; $i <= $maxSlots; $i++) {
            if (!in_array("backpack_$i", $occupied)) {
                return "backpack_$i";
            }
        }
        return null;
    }

    public function logActivity(Character $character, string $type, string $message, array $metadata = []): void
    {
        \App\Models\CharacterLog::create([
            'character_id' => $character->id,
            'type' => $type,
            'message' => $message,
            'metadata' => $metadata,
        ]);

        // Cleanup: Keep only last 50 logs
        $logsToKeep = 50;
        $count = $character->logs()->count();

        if ($count > $logsToKeep) {
            // Simplified cleanup
            $character->logs()->orderBy('created_at', 'asc')->limit($count - $logsToKeep)->delete();
        }
    }
}
