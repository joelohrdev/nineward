<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->foreignId('head_coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['season_id', 'slug']);
            // Target of the roster's composite foreign key, which keeps each
            // roster entry's season the same as its team's.
            $table->unique(['id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
