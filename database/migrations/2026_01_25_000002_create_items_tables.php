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
        Schema::create('item_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // weapon, armor, material
            $table->json('base_stats')->nullable(); // {"min_dmg": 5, "max_dmg": 10, "armor": 5}
            $table->integer('min_level')->default(1);
            $table->string('class_restriction')->nullable(); // null = all, or specific class enum
            $table->timestamps();
        });

        Schema::create('item_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('item_template_id')->constrained('item_templates')->cascadeOnDelete();

            // Polymorphic Owner: User (Warehouse) or Character (Backpack/Equipped)
            $table->uuid('owner_id');
            $table->string('owner_type');
            $table->index(['owner_id', 'owner_type']);

            // Slot: 
            // If Owner=User, this is warehouse slot index (1-200).
            // If Owner=Character, this can be backpack slot index (1-42) OR Enum value for Equipment.
            // We store as string to accommodate both "1" and "head".
            $table->string('slot_id')->nullable();

            $table->integer('upgrade_level')->default(0);
            $table->json('bonuses')->nullable(); // [{"type": "strength", "value": 5, "rarity": "common"}]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_instances');
        Schema::dropIfExists('item_templates');
    }
};
