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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3);
            $table->string('status');
            $table->unsignedInteger('refunded_cents')->default(0);
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('dispute_status')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
