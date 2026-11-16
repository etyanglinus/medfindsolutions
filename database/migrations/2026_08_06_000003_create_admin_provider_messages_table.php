<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_provider_messages', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type', 20);
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('sender_type', 20);
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['provider_type', 'provider_id', 'created_at'], 'admin_provider_messages_thread_index');
        });
    }
    public function down(): void { Schema::dropIfExists('admin_provider_messages'); }
};
