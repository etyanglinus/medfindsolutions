<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('services', 'name')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('name')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
