<?php

namespace App\Services;

use App\Classes\CombatEntity;
use App\Models\Character;
use App\Models\Monster;

class CombatEngine
{
    public function simulate(Character $character, Monster $monster, ?int $seed = null): array
    {
        // 1. Seeding
        $seed = $seed ?? rand();
        mt_srand($seed);

        // 2. Setup Entities
        $hero = new CombatEntity($character);
        $enemy = new CombatEntity($monster);

        // 3. Timeline Setup
        // Sort by next action. Initial action is calculated based on interval logic?
        // Or everyone starts at 0 and rollout initiative?
        // Let's assume everyone starts at 0 + Interval.
        $hero->nextActionAt = $hero->getAttackInterval();
        $enemy->nextActionAt = $enemy->getAttackInterval();

        $log = [];
        $timeLimit = 60000; // 60s max fight duration to prevent inf loops
        $winner = null;

        // 4. Simulation Loop
        while (true) {
            // Find who acts next
            if ($hero->nextActionAt <= $enemy->nextActionAt) {
                $actor = $hero;
                $target = $enemy;
            } else {
                $actor = $enemy;
                $target = $hero;
            }

            $timestamp = $actor->nextActionAt;

            if ($timestamp > $timeLimit) {
                // Time up - Draw or loss? Mission says duration... this is combat duration.
                // If combat takes too long, maybe Defender wins?
                $winner = $enemy->id; // Time out = loss
                $log[] = ['tick' => $timestamp, 'type' => 'timeout', 'message' => 'Time limit reached.'];
                break;
            }

            // Execute Attack
            $result = $this->resolveAttack($actor, $target);

            // Log Event
            $log[] = [
                'tick' => round($timestamp),
                'attacker_id' => $actor->id,
                'attacker_name' => $actor->name,
                'defender_id' => $target->id,
                'type' => $result['type'], // hit, miss, crit
                'damage' => $result['damage'],
                'target_hp' => $target->currentHp,
                'message' => $this->formatMessage($actor, $target, $result)
            ];

            // Check Death
            if ($target->isDead()) {
                $winner = $actor->id;
                $log[] = ['tick' => round($timestamp), 'type' => 'death', 'target' => $target->name, 'message' => "{$target->name} has been defeated!"];
                break;
            }

            // Reschedule Actor
            $actor->nextActionAt += $actor->getAttackInterval();
        }

        return [
            'winner_id' => $winner,
            'is_victory' => $winner === $hero->id,
            'seed' => $seed,
            'log' => $log,
            'final_hp' => [
                'hero' => $hero->currentHp,
                'enemy' => $enemy->currentHp
            ]
        ];
    }

    private function resolveAttack(CombatEntity $attacker, CombatEntity $defender): array
    {
        // 1. Hit Chance
        // Formula: Base 90% + (Acc - Eva)%?
        // If Acc >> Eva -> 100%. If Acc << Eva -> Low.
        // Simple: 85 + (Acc - Eva). Min 20, Max 100.
        $hitChance = 85 + ($attacker->accuracy - $defender->evasion);
        $hitChance = max(20, min(100, $hitChance));

        if (mt_rand(1, 100) > $hitChance) {
            return ['type' => 'miss', 'damage' => 0];
        }

        // 2. Damage Roll
        $rawDmg = mt_rand($attacker->minDmg, $attacker->maxDmg);

        // 3. Crit Check
        // Placeholder crit chance based on accuracy/dex? Or need crit stat.
        // Assuming base 5% + (Accuracy * 0.1)%
        $critChance = 5 + ($attacker->accuracy * 0.1);
        $isCrit = mt_rand(1, 100) <= $critChance;

        if ($isCrit) {
            $rawDmg = (int) ($rawDmg * 1.5);
        }

        // 4. Mitigation
        // Defense reduces damage flat? or percent?
        // MMORPG style: Dmg * (100 / (100 + Def))? -> Diminishing returns.
        // Let's use simple reduction for now: Damage - (Defense / 2). Min 1.
        $mitigation = (int) ($defender->defense / 2);
        $finalDmg = max(1, $rawDmg - $mitigation);

        $defender->takeDamage($finalDmg);

        return [
            'type' => $isCrit ? 'crit' : 'hit',
            'damage' => $finalDmg
        ];
    }

    private function formatMessage(CombatEntity $attacker, CombatEntity $defender, array $result): string
    {
        if ($result['type'] === 'miss') {
            return "{$attacker->name} attacks {$defender->name} but misses!";
        }
        $typeStr = $result['type'] === 'crit' ? "critically hits" : "hits";
        return "{$attacker->name} {$typeStr} {$defender->name} for {$result['damage']} damage!";
    }
}
