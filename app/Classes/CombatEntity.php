<?php

namespace App\Classes;

use App\Models\Character;
use App\Models\Monster;

class CombatEntity
{
    public string $id;
    public string $name;
    public string $type; // 'character' or 'monster'

    public int $maxHp;
    public int $currentHp;

    public int $minDmg;
    public int $maxDmg;

    public int $speed;
    public int $defense;

    public int $accuracy; // Derived from Dex
    public int $evasion; // Derived from Dex

    // Elemental Resistances (0-100)
    public array $resistances = [];

    // State
    public float $nextActionAt = 0; // ms
    public float $attackSpeed = 1.0;

    public function __construct($entity)
    {
        if ($entity instanceof Character) {
            $this->initFromCharacter($entity);
        } elseif ($entity instanceof Monster) {
            $this->initFromMonster($entity);
        }
    }

    private function initFromCharacter(Character $char)
    {
        $this->id = $char->id;
        $this->name = $char->name;
        $this->type = 'character';

        $stats = $char->stats->computed_stats;

        // Base 1.0, DB column added to 'character_stats' but computed_stats might not be refreshed yet.
        // Also fallback to the direct column if not in computed dict?
        // Actually, logic usually syncs DB stats to computed_stats JSON.
        // For now, let's grab from DB relation if not in JSON, but better to assume JSON is SSOT.
        // Except we just added the column, so existing JSONs don't have it.
        // We should probably rely on the `$char->stats->attack_speed` column.
        $this->attackSpeed = (float) ($char->stats->attack_speed ?? 1.0);

        $this->maxHp = $stats['max_hp'] ?? 100;
        $this->currentHp = $this->maxHp; // Start full for sim

        $this->minDmg = $stats['damage_min'] ?? 1;
        $this->maxDmg = $stats['damage_max'] ?? 2;

        $this->speed = 10 + ($stats['speed_bonus'] ?? 0); // Deprecated? Kept for avoid break

        $this->defense = $stats['defense'] ?? 0;

        $dex = $stats['dexterity'] ?? 1;
        $this->accuracy = $dex * 2;
        $this->evasion = $dex; // Evasion harder than Accuracy

        $this->resistances = [
            'fire' => $stats['resistance_fire'] ?? 0,
            'water' => $stats['resistance_water'] ?? 0,
            'wind' => $stats['resistance_wind'] ?? 0,
            'earth' => $stats['resistance_earth'] ?? 0,
        ];
    }

    private function initFromMonster(Monster $monster)
    {
        $this->id = (string) $monster->id;
        $this->name = $monster->name;
        $this->type = 'monster';

        $this->maxHp = $monster->hp;
        $this->currentHp = $monster->hp;

        $this->minDmg = $monster->min_dmg;
        $this->maxDmg = $monster->max_dmg;

        $this->speed = $monster->speed;
        $this->attackSpeed = 1.0 + ($monster->speed * 0.01); // Approximation for monsters using speed

        $this->defense = 0; // Monsters usually low armor, just HP

        // Monsters need derived accuracy/evasion? 
        // Let's assume standard based on Speed or Level (Monster doesn't have level explicitly here, map min_level?).
        // Let's assume Speed acts as Dex for Monsters.
        $this->accuracy = $monster->speed * 2;
        $this->evasion = $monster->speed;

        $this->resistances = [
            'fire' => 0,
            'water' => 0,
            'wind' => 0,
            'earth' => 0
        ];
        // Parse element from string 'fire' -> 20% res to own element?
        if ($monster->element) {
            $this->resistances[$monster->element] = 20;
        }
    }

    public function takeDamage(int $amount): int
    {
        $this->currentHp -= $amount;
        if ($this->currentHp < 0)
            $this->currentHp = 0;
        return $this->currentHp;
    }

    public function isDead(): bool
    {
        return $this->currentHp <= 0;
    }

    public function getAttackInterval(): float
    {
        // 1000ms / attacks_per_second
        if ($this->attackSpeed <= 0)
            return 3000;
        return 3000 / $this->attackSpeed;
    }

    public function getCritChance(): float
    {
        // Base 5% + (Accuracy * 0.1)%
        return 5 + ($this->accuracy * 0.1);
    }

    public function getCritMultiplier(): float
    {
        return 1.5;
    }
}
