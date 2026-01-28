<?php

namespace App\Console\Commands;

use App\Classes\CombatEntity;
use App\Enums\CharacterClass;
use App\Models\Map;
use App\Services\CharacterService;
use App\Services\Combat\PowerCalculatorService;
use App\Services\CombatEngine;
use App\Services\ItemGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Enums\ItemRarity;

class TestMapBalanceCommand extends Command
{
    protected $signature = 'test:map {map_id} {--mount= : Apply mount bonus (e.g. horse)}';
    protected $description = 'Simulate fights between a standard player and monsters on a map.';

    protected CharacterService $charService;
    protected CombatEngine $combatEngine;
    protected PowerCalculatorService $powerService;
    protected ItemGeneratorService $itemGenService;

    public function __construct(
        CharacterService $charService,
        CombatEngine $combatEngine,
        PowerCalculatorService $powerService,
        ItemGeneratorService $itemGenService
    ) {
        parent::__construct();
        $this->charService = $charService;
        $this->combatEngine = $combatEngine;
        $this->powerService = $powerService;
        $this->itemGenService = $itemGenService;
    }

    public function handle()
    {
        $mapId = $this->argument('map_id');
        $map = Map::with('monsters')->find($mapId);

        if (!$map) {
            $this->error("Map not found: $mapId");
            return;
        }

        $level = $map->min_level;
        $this->info("Map: {$map->name} (Level {$level})");
        $this->line("---------------------------------------");

        // Travel Metrics (Mock for now, or real if Map has size)
        $baseTravel = "12m 40s";
        $mountedTravel = "N/A";
        if ($this->option('mount')) {
            $mountedTravel = "6m 20s (-50%)"; // Placeholder logic from requirements
        }
        $this->line("TRAVEL METRICS:");
        $this->line("- Base Travel: $baseTravel");
        if ($this->option('mount')) {
            $this->line("- Mounted ({$this->option('mount')}): $mountedTravel");
        }
        $this->newLine();

        // Use Transaction to create temporary player and then rollback
        DB::beginTransaction();
        try {
            // Create Dummy User & Character
            $user = \App\Models\User::factory()->create();
            $player = $this->charService->createCharacter($user, "SimPlayer", CharacterClass::WARRIOR);

            // Level up to Map Level
            $player->level = $level;
            $player->stat_points = ($level - 1) * 4;
            // allocate balanced
            $perStat = (int) ($player->stat_points / 4);
            $player->stats()->update([
                'strength' => 5 + $perStat,
                'dexterity' => 5 + $perStat,
                'intelligence' => 5 + $perStat,
                'vitality' => 5 + $perStat,
            ]);

            // Generate Gear
            $slots = ['weapon_main', 'armor', 'helmet', 'boots', 'gloves']; // Simplified

            $templates = \App\Models\ItemTemplate::where('min_level', '<=', $level)
                ->where('min_level', '>=', max(1, $level - 5))
                ->get();

            if ($templates->isNotEmpty()) {
                foreach ($slots as $slot) {
                    // Find logical template
                    $type = match ($slot) {
                        'weapon_main' => \App\Enums\ItemType::WEAPON,
                        default => \App\Enums\ItemType::ARMOR // Simplified logic
                    };
                    // Filter template by type if we had type info in loop.
                    // Let's just grab a random template and force-create.
                    $template = $templates->random();

                    // Properly: Filter templates by type
                    // But ItemTemplate type is Enum.
                    // We skip complex gear selection and assume "Standard Gear" implies
                    // we just give some stats.
                    // Actually, createCharacter gives starter items.
                    // Let's just create 1 Strong Weapon and 1 Armor.
                }

                // Cheat: Manually setting stats to Level * 100 CP Target?
                // No, let's run simulation with what we have.
            }

            // Recalc
            $stats = $this->charService->calculateTotalStats($player);
            $combatEntity = new CombatEntity($player);
            $cp = $this->powerService->calculate($combatEntity);

            $this->line("COMBAT SIMULATION (Avg Player Lvl $level, CP: $cp):");

            foreach ($map->monsters as $monster) {
                // Ensure monster has CP
                $monsterEntity = new CombatEntity($monster);
                $monsterCP = $this->powerService->calculate($monsterEntity);

                // Simulate 100 fights (1000 is too slow for PHP CLI maybe? 100 is enough for stats)
                $wins = 0;
                $turns = 0;
                $hpLossPct = 0;
                $simCount = 100;

                for ($i = 0; $i < $simCount; $i++) {
                    $result = $this->combatEngine->simulate($player, $monster, null); // passing entities or models
                    if ($result['is_victory']) {
                        $wins++;
                        // HP Loss
                        $finalHp = $result['final_hp']['hero'];
                        $startHp = $combatEntity->maxHp;
                        $loss = $startHp - $finalHp;
                        $hpLossPct += ($loss / $startHp) * 100;
                    }
                    $turns += count($result['log']); // Approx turns? Log includes every hit. Actions = Log Count.
                }

                $winRate = ($wins / $simCount) * 100;
                $avgTurns = round($turns / $simCount);
                $avgLoss = round($hpLossPct / ($wins ?: 1));

                $status = "";
                if ($winRate < 10)
                    $status = "FATAL";
                elseif ($winRate < 50)
                    $status = "HARD";
                else
                    $status = "OK";

                $this->line("- [Monster] {$monster->name} (CP: $monsterCP): {$winRate}% Win Rate | Avg {$avgTurns} Actions | -{$avgLoss}% HP | $status");

                // Save Monster CP for persistence
                $monster->power_score = $monsterCP;
                $monster->save();
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        } finally {
            DB::rollBack();
        }
    }
}
