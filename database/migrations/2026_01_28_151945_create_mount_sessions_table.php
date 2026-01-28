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
        Schema::create('mount_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('character_id')->constrained()->cascadeOnDelete();
            $table->string('mount_type');
            $table->timestamp('rented_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['character_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mount_sessions');
    }
};
