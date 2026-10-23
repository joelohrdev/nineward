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
        Schema::create('stripe_connect_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $stripeId = $table->string('stripe_account_id')->unique();

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $stripeId->collation('utf8mb4_bin');
            }

            $table->boolean('charges_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);
            $table->boolean('details_submitted')->default(false);
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_connect_accounts');
    }
};
