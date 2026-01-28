<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\ItemInstance;
use App\Models\ItemTemplate;
use App\Services\Combat\PowerCalculatorService;
use App\Services\ItemGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestItemScalingCommand extends Command
{
    protected $signature = 'test:item {item_id?} {--template=} {--level=1}';
    protected $description = 'Check item CP against LevelCurve.';

    protected PowerCalculatorService $powerService;
    protected ItemGeneratorService $itemGenService;

    public function __construct(PowerCalculatorService $powerService, ItemGeneratorService $itemGenService)
    {
        parent::__construct();
        $this->powerService = $powerService;
        $this->itemGenService = $itemGenService;
    }

    public function handle()
    {
        $itemId = $this->argument('item_id');
        $templateId = $this->option('template');
        $level = (int) $this->option('level');

        // Setup Temporary Character for base comparison
        // We need a character to calculate delta.
        // We'll create a dummy one with stats matching the item level (roughly).
        // Item Level:
        // If Instance: use Instance->template->min_level + upgrade?
        // If Template: use min_level.

        DB::beginTransaction();
        try {
            if ($itemId) {
                $item = ItemInstance::with('template')->findOrFail($itemId);
                $targetLevel = $item->template->min_level; // or upgrade logic?
                $this->info("Testing Item Instance: {$item->id} ({$item->template->name})");
            } elseif ($templateId) {
                $template = ItemTemplate::findOrFail($templateId);
                $this->info("Testing Template: {$template->name} (Generating Instance...)");
                // Generate temporary instance
                $dummyUser = \App\Models\User::factory()->create();
                $item = $this->itemGenService->generateInstance($template, $dummyUser, \App\Enums\ItemRarity::COMMON);
                $targetLevel = $template->min_level;
            } else {
                $this->error("Please provide item_id or --template ID.");
                return;
            }

            // Create Standard Character at Item's Level
            $user = \App\Models\User::factory()->create();
            $character = Character::create([
                'user_id' => $user->id,
                'name' => 'ScaleTester',
                'class' => \App\Enums\CharacterClass::WARRIOR,
                'level' => $targetLevel,
            ]);
            // Give base stats for level
            $statPoints = ($targetLevel - 1) * 4;
            $perStat = (int) ($statPoints / 4);
            $character->stats()->update([
                'strength' => 5 + $perStat,
                'dexterity' => 5 + $perStat,
                'intelligence' => 5 + $perStat,
                'vitality' => 5 + $perStat,
                'computed_stats' => [] // Should trigger recalc?
            ]);
            // Need CharacterService logic to init computed_stats.
            // Simplified: we rely on PowerCalculatorService::calculateItemDelta doing the heavy lifting,
            // but calculateItemDelta reads computed_stats.
            // We should init computed_stats manually or call CharacterService.
            // For now, let's manually prep computed_stats array.
            $baseStats = [
                'strength' => 5 + $perStat,
                'dexterity' => 5 + $perStat,
                'intelligence' => 5 + $perStat,
                'vitality' => 5 + $perStat,
                'max_hp' => (5 + $perStat) * 10,
                'defense' => 0,
                'damage_min' => 1,
                'damage_max' => 2,
                'attack_speed' => 1.0
                // ignore others
            ];
            $character->stats->computed_stats = $baseStats;
            $character->stats->save();

            // Calculate Delta
            $cpDelta = $this->powerService->calculateItemDelta($item, $character);

            // Expected Curve
            // Formula: Item Target = Level * 10 (Heuristic from earlier thought)
            $expected = $targetLevel * 10;

            $diff = $expected > 0 ? (($cpDelta - $expected) / $expected) * 100 : 0;
            $diffStr = round($diff, 1) . '%';

            $status = "OK";
            if ($diff > 20)
                $status = "<fg=red>OVERPOWERED (>{$diffStr})</>";
            elseif ($diff < -50)
                $status = "<fg=yellow>UNDERPOWERED ({$diffStr})</>";

            $this->line("Item CP: {$cpDelta}");
            $this->line("Target CP (Lvl * 10): {$expected}");
            $this->line("Status: {$status}");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        } finally {
            DB::rollBack();
        }
    }
}
