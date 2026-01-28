<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Map;
use App\Models\Monster;
use App\Models\MountSession;
use App\Models\User;
use App\Services\MountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MountSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed needed data if necessary, or just create models
        $this->seed();
    }

    public function test_can_rent_mount_with_sufficient_gold()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'gold' => 2000 // Enough for Donkey (1000)
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/mounts/rent', [
                'character_id' => $character->id,
                'mount_type' => 'donkey'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('mount_sessions', [
            'character_id' => $character->id,
            'mount_type' => 'donkey'
        ]);

        $character->refresh();
        $this->assertEquals(1000, $character->gold);
    }

    public function test_cannot_rent_mount_without_sufficient_gold()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'gold' => 500 // Not enough for Donkey (1000)
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/mounts/rent', [
                'character_id' => $character->id,
                'mount_type' => 'donkey'
            ]);

        $response->assertStatus(400); // Or whatever error code set
        $this->assertDatabaseMissing('mount_sessions', [
            'character_id' => $character->id
        ]);
    }

    public function test_active_mount_endpoint_returns_data()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $mount = MountSession::create([
            'character_id' => $character->id,
            'mount_type' => 'horse',
            'rented_at' => now(),
            'expires_at' => now()->addDays(7)
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/mounts/active?character_id={$character->id}");

        $response->assertStatus(200)
            ->assertJsonPath('active_mount.mount_type', 'horse');
    }

    public function test_mission_time_reduction()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'level' => 10]);

        // Create Map and Monster
        $map = Map::factory()->create(['min_level' => 1]);
        $monster = Monster::factory()->create(['map_id' => $map->id]);

        // Rent Horse (50% reduction)
        $mount = MountSession::create([
            'character_id' => $character->id,
            'mount_type' => 'horse',
            'rented_at' => now(),
            'expires_at' => now()->addDays(7)
        ]);

        // Start Mission
        $response = $this->actingAs($user)
            ->postJson('/api/mission/start', [
                'character_id' => $character->id,
                'map_id' => $map->id
            ]);

        $response->assertStatus(200);

        $mission = \App\Models\Mission::where('character_id', $character->id)->first();

        $service = app(MountService::class);
        $reduction = $service->calculateReducedDuration(35, $mount); // 50% of 35 is 17.5. Reduced: 17.5 -> round(17.5) = 18.

        // Base: 35s. Expected with reduction: 18s.
        // Base: 35s. Expected with reduction: 18s.
        $diff = abs($mission->ends_at->diffInSeconds($mission->started_at));

        // Assert logic
        $this->assertTrue($diff >= 17 && $diff <= 18, "Diff was {$diff}, expected 17 or 18");
    }

    public function test_mount_expiration_job()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        // Create expired mount
        MountSession::create([
            'character_id' => $character->id,
            'mount_type' => 'donkey',
            'rented_at' => now()->subDays(8),
            'expires_at' => now()->subMinute()
        ]);

        $this->assertDatabaseCount('mount_sessions', 1);

        // Run Job
        (new \App\Jobs\CheckMountExpirations)->handle();

        $this->assertDatabaseCount('mount_sessions', 0);
    }
}
