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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('possible_duplicate_player_id')->nullable()->constrained('players')->nullOnDelete();
            // Answers include minors' medical and contact details, so they're encrypted.
            $table->text('data');
            $table->string('status');
            $table->string('review_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
            $table->index(['organization_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
