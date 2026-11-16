<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('years_of_experience')->nullable()->after('consultation_fee');
            $table->text('address')->nullable()->after('description');
            $table->longText('educations')->nullable()->after('address');
            $table->longText('experience')->nullable()->after('educations');
            $table->longText('qualifications')->nullable()->after('experience');
            $table->text('seo_title')->nullable()->after('qualifications');
            $table->text('seo_description')->nullable()->after('seo_title');
        });
    }

    public function down(): void {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'years_of_experience', 'address', 'educations', 'experience',
                'qualifications', 'seo_title', 'seo_description',
            ]);
        });
    }
};
