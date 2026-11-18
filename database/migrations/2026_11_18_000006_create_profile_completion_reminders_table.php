<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which "finish your profile" reminders each provider has had (each stage is sent once). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_completion_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type', 20);
            $table->unsignedBigInteger('provider_id');
            $table->unsignedSmallInteger('stage'); // days after the plan started: 1, 3, 7, 14
            $table->timestamp('sent_at');
            $table->unique(['provider_type', 'provider_id', 'stage'], 'profile_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_completion_reminders');
    }
};
