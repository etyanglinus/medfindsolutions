<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('provider_documents', 'verification_body')) {
            Schema::table('provider_documents', function (Blueprint $table) {
                $table->string('verification_body')->nullable();
                $table->date('accreditation_expires_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('provider_documents', function (Blueprint $table) {
            $table->dropColumn(['verification_body', 'accreditation_expires_at']);
        });
    }
};
