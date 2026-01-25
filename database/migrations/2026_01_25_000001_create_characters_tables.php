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
        Schema::create('characters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('class'); // Warrior, Assassin, Mage
            $table->integer('level')->default(1);
            $table->bigInteger('experience')->default(0);
            $table->bigInteger('gold')->default(0);
            $table->integer('stat_points')->default(0);
            $table->unsignedBigInteger('current_map_id')->nullable();
            // We will add the foreign key constraint for map_id in the world migration 
            // or here if we create maps first. Since we are creating characters first 
            // in my plan, I'll add the constraint later or just keep it as integer for now.
            // Better key it as nullable integer.
            $table->timestamps();
        });

        Schema::create('character_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->integer('strength')->default(5);
            $table->integer('dexterity')->default(5);
            $table->integer('intelligence')->default(5);
            $table->integer('vitality')->default(5);
            // Resistances
            $table->integer('resistance_wind')->default(0);
            $table->integer('resistance_fire')->default(0);
            $table->integer('resistance_water')->default(0);
            $table->integer('resistance_earth')->default(0);

            $table->json('computed_stats')->nullable(); // Cached Total Stats
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_stats');
        Schema::dropIfExists('characters');
    }
};
