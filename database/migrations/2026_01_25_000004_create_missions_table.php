<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('ends_at');
            $table->string('status'); // MissionStatus Enum

            // To store rewards if we want persistence before claim finalization or for history
            $table->json('rewards_json')->nullable();
            // e.g. {"gold": 100, "xp": 50, "items": [uuid1, uuid2]}

            $table->timestamps();

            // Allow only one active mission per character?
            // Index for faster lookups
            $table->index(['character_id', 'status']);
        });

        // Add base_exp and base_gold to monsters
        Schema::table('monsters', function (Blueprint $table) {
            $table->integer('base_exp')->default(10);
            $table->integer('base_gold')->default(5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missions');
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn(['base_exp', 'base_gold']);
        });
    }
};
