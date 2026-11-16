<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->restrictOnDelete();
            $table->foreignId('subscription_package_id')->nullable()->constrained('subscription_packages')->nullOnDelete();
            $table->string('plan_code', 80);
            $table->string('package_name', 160);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->unsignedSmallInteger('duration_months')->default(1);
            $table->string('status', 30)->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('provider_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->restrictOnDelete();
            $table->foreignId('provider_subscription_id')->nullable()->constrained('provider_subscriptions')->nullOnDelete();
            $table->string('gateway', 40);
            $table->string('gateway_reference')->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('status', 30)->default('initiated');
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_reference']);
            $table->index(['provider_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payment_transactions');
        Schema::dropIfExists('provider_subscriptions');
    }
};
