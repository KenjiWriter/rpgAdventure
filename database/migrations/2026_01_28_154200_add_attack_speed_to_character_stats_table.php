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
            $table->decimal('attack_speed', 5, 2)->default(1.00)->after('vitality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_stats', function (Blueprint $table) {
            $table->dropColumn('attack_speed');
        });
    }
};
