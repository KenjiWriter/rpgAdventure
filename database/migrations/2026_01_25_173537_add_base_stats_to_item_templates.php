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
        Schema::table('item_templates', function (Blueprint $table) {
            $table->integer('base_damage_min')->default(0);
            $table->integer('base_damage_max')->default(0);
            $table->integer('base_defense')->default(0);
            // min_level already exists in original create logic? 
            // The original create_item_templates migration has min_level.
            // But user asked to "add required_level". We have min_level. 
            // I'll stick to min_level as the column name but ensure it's used as Required Level.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_templates', function (Blueprint $table) {
            //
        });
    }
};
