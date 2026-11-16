<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Medicine\app\Models\MedicineType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicine_type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MedicineType::class)->cascadeOnDelete();
            $table->string('lang_code');
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_type_translations');
    }
};
