<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthcare_institutions', function (Blueprint $table) {
            foreach (['registration_number', 'accreditation_body', 'accreditation_expires_at', 'application_url', 'location_map_url', 'intake_campaign_title', 'intake_campaign_ends_at'] as $column) {
                if (! Schema::hasColumn('healthcare_institutions', $column)) {
                    if ($column === 'accreditation_expires_at' || $column === 'intake_campaign_ends_at') $table->date($column)->nullable();
                    elseif ($column === 'application_url' || $column === 'location_map_url') $table->string($column, 1000)->nullable();
                    else $table->string($column)->nullable();
                }
            }
        });

        Schema::table('healthcare_courses', function (Blueprint $table) {
            if (! Schema::hasColumn('healthcare_courses', 'is_featured')) $table->boolean('is_featured')->default(false);
            if (! Schema::hasColumn('healthcare_courses', 'intake_starts_at')) $table->date('intake_starts_at')->nullable();
            if (! Schema::hasColumn('healthcare_courses', 'intake_ends_at')) $table->date('intake_ends_at')->nullable();
        });

        if (! Schema::hasTable('healthcare_institution_enquiries')) {
            Schema::create('healthcare_institution_enquiries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('healthcare_institution_id');
                $table->unsignedBigInteger('healthcare_course_id')->nullable();
                $table->string('name');
                $table->string('email');
                $table->string('phone', 40)->nullable();
                $table->string('organisation')->nullable();
                $table->text('message');
                $table->string('status', 30)->default('new');
                $table->timestamps();
                $table->index(['healthcare_institution_id', 'status', 'created_at'], 'hie_school_status_created_idx');
                $table->foreign('healthcare_institution_id', 'hie_institution_fk')->references('id')->on('healthcare_institutions')->cascadeOnDelete();
                $table->foreign('healthcare_course_id', 'hie_course_fk')->references('id')->on('healthcare_courses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('healthcare_institution_enquiries');
        Schema::table('healthcare_courses', function (Blueprint $table) {
            $drop = array_values(array_filter(['is_featured', 'intake_starts_at', 'intake_ends_at'], fn ($c) => Schema::hasColumn('healthcare_courses', $c)));
            if ($drop) $table->dropColumn($drop);
        });
        Schema::table('healthcare_institutions', function (Blueprint $table) {
            $drop = array_values(array_filter(['registration_number', 'accreditation_body', 'accreditation_expires_at', 'application_url', 'location_map_url', 'intake_campaign_title', 'intake_campaign_ends_at'], fn ($c) => Schema::hasColumn('healthcare_institutions', $c)));
            if ($drop) $table->dropColumn($drop);
        });
    }
};
