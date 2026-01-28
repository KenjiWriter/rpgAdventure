<?php

namespace App\Services;

use App\Models\Character;
use App\Models\MountSession;
use App\Services\GameService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class MountService
{
    // Mount Configuration
    // Reduction is percentage points (e.g., 25 means 25% reduced duration)
    // Duration is in days
    public const MOUNTS = [
        'donkey' => [
            'name' => 'Donkey',
            'cost' => 1000,
            'duration_days' => 7,
            'reduction_percent' => 25,
            'description' => 'A reliable donkey. Reduces travel time by 25%.'
        ],
        'horse' => [
            'name' => 'Horse',
            'cost' => 5000,
            'duration_days' => 7,
            'reduction_percent' => 50,
            'description' => 'A fast horse. Reduces travel time by 50%.'
        ],
        'wyvern' => [
            'name' => 'Wyvern',
            'cost' => 25000,
            'duration_days' => 7,
            'reduction_percent' => 65,
            'description' => 'A swift wyvern. Reduces travel time by 65%.'
        ],
        'griffin' => [
            'name' => 'Griffin',
            'cost' => 100000,
            'duration_days' => 7,
            'reduction_percent' => 80,
            'description' => 'A legendary griffin. Reduces travel time by 80%.'
        ],
    ];

    public function getAvailableMounts(): array
    {
        return self::MOUNTS;
    }

    public function getActiveMount(Character $character): ?MountSession
    {
        return MountSession::where('character_id', $character->id)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function rentMount(Character $character, string $mountType): MountSession
    {
        if (!array_key_exists($mountType, self::MOUNTS)) {
            throw new Exception("Invalid mount type: {$mountType}");
        }

        $config = self::MOUNTS[$mountType];

        // Use a transaction to ensure gold is deducted and mount is added atomically
        return DB::transaction(function () use ($character, $mountType, $config) {
            // Reload character to ensure fresh gold balance
            $character->refresh();

            if ($character->gold < $config['cost']) {
                throw new Exception("Insufficient gold. Required: {$config['cost']}, Available: {$character->gold}");
            }

            // Remove existing active mount if any (or we could prevent it)
            // Policy: Overwrite existing mount properly
            MountSession::where('character_id', $character->id)
                ->where('expires_at', '>', now())
                ->delete();

            // Deduct Gold
            $character->decrement('gold', $config['cost']);

            // Create Session
            return MountSession::create([
                'character_id' => $character->id,
                'mount_type' => $mountType,
                'rented_at' => now(),
                'expires_at' => now()->addDays($config['duration_days']),
            ]);
        });
    }

    public function calculateReducedDuration(int $baseSeconds, ?MountSession $activeMount): int
    {
        if (!$activeMount || !array_key_exists($activeMount->mount_type, self::MOUNTS)) {
            return $baseSeconds;
        }

        $reductionPercent = self::MOUNTS[$activeMount->mount_type]['reduction_percent'];
        $reduction = $baseSeconds * ($reductionPercent / 100);

        return max(0, (int) round($baseSeconds - $reduction));
    }
}
