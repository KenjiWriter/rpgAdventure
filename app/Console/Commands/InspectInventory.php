<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InspectInventory extends Command
{
    protected $signature = 'app:inspect-inventory {character_name} {--repair : Assign ghost items to free slots}';
    protected $description = 'Inspect and repair inventory items for a character';

    public function handle()
    {
        $name = $this->argument('character_name');
        $character = \App\Models\Character::where('name', $name)->first();

        if (!$character) {
            $this->error("Character {$name} not found.");
            return;
        }

        $this->info("Inspecting Inventory for: {$character->name} (Level {$character->level})");

        $items = $character->items()->with('template')->get();
        $headers = ['Item ID', 'Template', 'Slot ID', 'Rarity', 'Raw Bonuses'];
        $rows = [];

        $ghostItems = [];
        $occupiedSlots = [];

        foreach ($items as $item) {
            $rows[] = [
                $item->id,
                $item->template->name ?? 'UNKNOWN',
                $item->slot_id ?? 'NULL (GHOST)',
                $item->template?->rarity?->value ?? 'N/A',
                json_encode($item->bonuses),
            ];

            if ($item->slot_id) {
                $occupiedSlots[] = $item->slot_id;
            } else {
                $ghostItems[] = $item;
            }
        }

        $this->table($headers, $rows);

        $this->info("Total Items: " . count($items));
        $this->info("Ghost Items: " . count($ghostItems));

        if ($this->option('repair') && count($ghostItems) > 0) {
            $this->warn("Repairing " . count($ghostItems) . " ghost items...");

            $maxSlots = $character->inventory_slots ?? 30;
            $fixed = 0;

            foreach ($ghostItems as $item) {
                // Find free slot
                $freeSlot = null;
                for ($i = 1; $i <= $maxSlots; $i++) {
                    if (!in_array("backpack_$i", $occupiedSlots)) {
                        $freeSlot = "backpack_$i";
                        $occupiedSlots[] = $freeSlot; // Mark as taken immediately for next iteration
                        break;
                    }
                }

                if ($freeSlot) {
                    $item->update(['slot_id' => $freeSlot]);
                    $this->info("Assigned {$item->template->name} ({$item->id}) to {$freeSlot}");
                    $fixed++;
                } else {
                    $this->error("No free slots available for {$item->template->name}!");
                }
            }
            $this->info("Repaired {$fixed} items.");
        }
    }
}
