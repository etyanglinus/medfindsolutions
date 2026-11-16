<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_document_id')->constrained('provider_documents')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('previous_status', 30);
            $table->string('status', 30);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['provider_document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_document_reviews');
    }
};
