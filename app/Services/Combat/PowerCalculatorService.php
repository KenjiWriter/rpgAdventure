<?php

namespace App\Services\Combat;

use App\Classes\CombatEntity;
use App\Enums\CharacterClass;
use App\Models\Character;
use App\Models\ItemInstance;
use App\Models\ItemTemplate;

class PowerCalculatorService
{
    /**
     * Calculate CP for a given CombatEntity (Player or Monster).
     *
     * Formula:
     * CP = Survivability * 0.1 + Offensive * 1.5
     */
    public function calculate(CombatEntity $entity): int
    {
        $survivability = $this->calculateSurvivability($entity);
        $offensive = $this->calculateOffensive($entity);

        return (int) round($survivability * 0.1 + $offensive * 1.5);
    }

    /**
     * Calculate CP Delta for valid item comparison.
     * item_cp = new_cp - base_cp
     */
    public function calculateItemDelta(ItemInstance $item, Character $character): int
    {
        // 1. Base CP
        $baseEntity = new CombatEntity($character);
        $baseCP = $this->calculate($baseEntity);

        // 2. Prepare Base Stats
        $stats = $character->stats->computed_stats ?? [];
        // Ensure defaults
        $baseStats = array_merge([
            'strength' => 0,
            'dexterity' => 0,
            'intelligence' => 0,
            'vitality' => 0,
            'damage_min' => 0,
            'damage_max' => 0,
            'defense' => 0,
            'attack_speed' => 1.0,
            'resistance_fire' => 0,
            'resistance_water' => 0,
            'resistance_wind' => 0,
            'resistance_earth' => 0,
        ], $stats);

        // 3. Determine Main Stat for Damage Calculation
        $mainStatKey = match ($character->class) {
            CharacterClass::WARRIOR => 'strength',
            CharacterClass::ASSASSIN => 'dexterity',
            CharacterClass::MAGE => 'intelligence',
            default => 'strength',
        };

        // 4. Calculate Old Stat Bonus
        $oldMainStat = $baseStats[$mainStatKey];
        $oldStatBonus = (int) ($oldMainStat * 1.5);

        // 5. Apply Item Bonuses to create New Stats
        $newStats = $baseStats;
        $multiplier = 1 + ($item->upgrade_level * 0.10);
        $template = $item->template;

        // Apply Template Base
        if ($template) {
            if ($template->base_damage_min) {
                // Add Base Weapon Damage directly to Damage
                $valMin = (int) ($template->base_damage_min * $multiplier);
                $valMax = (int) (($template->base_damage_max ?: $template->base_damage_min) * $multiplier);
                $newStats['damage_min'] += $valMin;
                $newStats['damage_max'] += $valMax;
            }
            if ($template->base_defense) {
                $newStats['defense'] += (int) ($template->base_defense * $multiplier);
            }
            if ($template->base_stats) {
                foreach ($template->base_stats as $k => $v) {
                    $val = (int) ($v * $multiplier);
                    if (isset($newStats[$k]))
                        $newStats[$k] += $val;
                    else
                        $newStats[$k] = $val;
                }
            }
        }

        // Apply Item Bonuses
        $bonuses = $item->bonuses ?? [];
        foreach ($bonuses as $bonus) {
            if (isset($bonus['type']) && isset($bonus['value'])) {
                $k = $bonus['type'];
                $val = $bonus['value'];
                if (isset($newStats[$k]))
                    $newStats[$k] += $val;
                else
                    $newStats[$k] = $val;
            }
        }

        // 6. Recalculate Global Derived Stats (Damage from Stats, HP from Vit)

        // HP Update
        // Max HP = Vit * 10
        // We update max_hp based on new Vitality.
        // But $newStats['max_hp'] might have flat HP bonuses too (unlikely in this system but possible).
        // Let's assume max_hp in computed_stats is strictly derived + flat bonuses.
        // We can just add (DeltaVit * 10) to max_hp.
        $deltaVit = $newStats['vitality'] - $baseStats['vitality'];
        $newStats['max_hp'] = ($baseStats['max_hp'] ?? ($baseStats['vitality'] * 10)) + ($deltaVit * 10);

        // Damage Update
        // NewDamage = OldDamage + (DeltaMinStat * 1.5)
        $newMainStat = $newStats[$mainStatKey];
        $newStatBonus = (int) ($newMainStat * 1.5);
        $statBonusDelta = $newStatBonus - $oldStatBonus;

        $newStats['damage_min'] += $statBonusDelta;
        $newStats['damage_max'] += $statBonusDelta;

        // Attack Speed Update (Simple additive approximation for now if flat, or logic if percent)
        // If we have 'attack_speed' bonus, it's added.

        // 7. Create Hypothetical CombatEntity
        $newEntity = new CombatEntity($character);

        // Inject values
        $newEntity->maxHp = $newStats['max_hp'];
        $newEntity->currentHp = $newEntity->maxHp;

        $newEntity->minDmg = max(1, $newStats['damage_min']);
        $newEntity->maxDmg = max(2, $newStats['damage_max']);

        $newEntity->defense = $newStats['defense'];

        $dex = $newStats['dexterity'];
        $newEntity->accuracy = $dex * 2;
        $newEntity->evasion = $dex;

        $newEntity->attackSpeed = (float) $newStats['attack_speed'];

        // 8. Calculate New CP
        $newCP = $this->calculate($newEntity);

        return $newCP - $baseCP;
    }

    public function calculateSurvivability(CombatEntity $entity): float
    {
        // Survivability = HP × (1 + Defense/100)
        return $entity->maxHp * (1 + $entity->defense / 100);
    }

    public function calculateOffensive(CombatEntity $entity): float
    {
        // Offensive = (Attack × Accuracy/100) × (2000/AttackInterval) × (1 + CritChance × CritMultiplier/100)
        $attack = ($entity->minDmg + $entity->maxDmg) / 2;

        // Accuracy/100
        $accuracyFactor = $entity->accuracy / 100;

        $interval = $entity->getAttackInterval();
        $speedFactor = 2000 / ($interval > 0 ? $interval : 3000);

        $critChance = $entity->getCritChance();
        $critMult = $entity->getCritMultiplier();
        $critFactor = 1 + ($critChance * $critMult / 100);

        return ($attack * $accuracyFactor) * $speedFactor * $critFactor;
    }
}
