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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('objective_type'); // kill_monster, collect_item
            $table->string('objective_target'); // monster_id, item_name
            $table->integer('objective_count');
            $table->integer('reward_gold')->default(0);
            $table->integer('reward_xp')->default(0);
            // $table->foreignId('reward_item_template_id')->nullable()->constrained('item_templates'); // Assuming table exists or adding later
            $table->timestamps();
        });

        Schema::create('character_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->integer('progress')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_claimed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_quests');
        Schema::dropIfExists('quests');
    }
};
