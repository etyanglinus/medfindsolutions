<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type');
            $table->string('code');
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedTinyInteger('duration_months');
            $table->json('features');
            $table->json('entitlements');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['provider_type', 'code']);
        });
    }

    public function down(): void { Schema::dropIfExists('subscription_packages'); }
};
