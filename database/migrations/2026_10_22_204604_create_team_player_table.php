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
        Schema::create('team_player', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->string('position', 2)->nullable();
            $table->boolean('is_captain')->default(false);
            $table->timestamps();

            $table->foreign(['team_id', 'season_id'])
                ->references(['id', 'season_id'])
                ->on('teams')
                ->cascadeOnDelete();

            // A player is on at most one team per season.
            $table->unique(['season_id', 'player_id']);
            $table->unique(['team_id', 'jersey_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_player');
    }
};
