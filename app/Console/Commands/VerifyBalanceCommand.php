<?php

namespace App\Console\Commands;

use App\Classes\CombatEntity;
use App\Models\Map;
use App\Services\Combat\PowerCalculatorService;
use Illuminate\Console\Command;

class VerifyBalanceCommand extends Command
{
    protected $signature = 'verify:balance {--map=}';
    protected $description = 'Verify monster balance against level curve.';

    protected PowerCalculatorService $powerService;

    public function __construct(PowerCalculatorService $powerService)
    {
        parent::__construct();
        $this->powerService = $powerService;
    }

    public function handle()
    {
        $mapId = $this->option('map');

        $query = Map::with('monsters');
        if ($mapId) {
            $query->where('id', $mapId);
        }

        $maps = $query->get();

        foreach ($maps as $map) {
            $this->info("Checking Map: {$map->name} (Min Level {$map->min_level})");

            $targetCP = $map->min_level * 100;
            $this->comment("Target CP (Level * 100): $targetCP");

            $headers = ['Monster', 'Level', 'CP', 'Diff %', 'Status'];
            $rows = [];

            foreach ($map->monsters as $monster) {
                $entity = new CombatEntity($monster);
                $cp = $this->powerService->calculate($entity);

                // Save CP
                if ($monster->power_score !== $cp) {
                    $monster->power_score = $cp;
                    $monster->save();
                }

                $diff = $targetCP > 0 ? (($cp - $targetCP) / $targetCP) * 100 : 0;
                $diffStr = round($diff, 1) . '%';

                $status = 'OK';
                if ($diff > 50)
                    $status = '<fg=red>OVERPOWERED</>';
                elseif ($diff > 20)
                    $status = '<fg=yellow>STRONG</>';
                elseif ($diff < -80)
                    $status = '<fg=red>WEAK</>';
                elseif ($diff < -50)
                    $status = '<fg=yellow>UNDERPOWERED</>';

                $rows[] = [
                    $monster->name,
                    $map->min_level . " (Map)",
                    $cp,
                    $diffStr,
                    $status
                ];
            }

            $this->table($headers, $rows);
            $this->newLine();
        }
    }
}
