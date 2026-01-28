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
        Schema::table('character_stats', function (Blueprint $table) {
            $table->integer('power_score')->default(0)->index();
        });

        Schema::table('monsters', function (Blueprint $table) {
            $table->integer('power_score')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_stats', function (Blueprint $table) {
            $table->dropColumn('power_score');
        });

        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn('power_score');
        });
    }
};
