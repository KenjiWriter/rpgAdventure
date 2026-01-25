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

        $this->maxHp = $stats['max_hp'] ?? 100;
        $this->currentHp = $this->maxHp; // Start full for sim

        $this->minDmg = $stats['damage_min'] ?? 1;
        $this->maxDmg = $stats['damage_max'] ?? 2;

        // Base Speed + Bonus? Char has no base speed col, maybe Class base?
        // Let's assume stats have 'speed' or 'dexterity' impact.
        // Or assume base 10 + bonuses.
        $this->speed = 10 + ($stats['speed_bonus'] ?? 0);

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
        // Formula: 2000ms / (1 + Speed*0.01)
        // Using Speed property which for Character is ~10 + Bonuses.
        // 10 Speed -> 1.1x -> 1818ms.
        // 100 Speed -> 2.0x -> 1000ms.
        return 2000 / (1 + ($this->speed * 0.01));
    }
}
