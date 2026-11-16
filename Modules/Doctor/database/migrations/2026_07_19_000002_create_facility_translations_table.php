<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Doctor\app\Models\Facility;

return new class extends Migration {
    public function up(): void {
        Schema::create('facility_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Facility::class)->cascadeOnDelete();
            $table->string('lang_code');
            $table->string('licensing_body')->nullable();
            $table->text('about')->nullable();
            $table->text('address')->nullable();
            $table->longText('educations')->nullable();
            $table->longText('experience')->nullable();
            $table->longText('qualifications')->nullable();
            $table->text('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('facility_translations');
    }
};
