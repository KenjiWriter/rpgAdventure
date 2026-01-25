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
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('min_level')->default(1);
            $table->timestamps();
        });

        Schema::create('monsters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('hp');
            $table->integer('min_dmg');
            $table->integer('max_dmg');
            $table->integer('speed'); // Base attack speed
            $table->string('element')->nullable(); // wind, fire, water, earth
            $table->json('drops_json')->nullable(); // Probabilities of drops
            $table->foreignId('map_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Add constraint to characters table now that maps exist
        // Note: Using a separate schema table call to avoid order dependency issues if validation is strict, 
        // essentially circular dependency is avoided if we just let the integer be there, but foreign key needs the table.
        // Doing it here is safe as maps table is created just above.
        Schema::table('characters', function (Blueprint $table) {
            $table->foreign('current_map_id')->references('id')->on('maps')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign(['current_map_id']);
        });
        Schema::dropIfExists('monsters');
        Schema::dropIfExists('maps');
    }
};
