<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Location;

return new class extends Migration {
    public function up(): void {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Department::class)->constrained();
            $table->foreignIdFor(Location::class)->constrained();
            $table->string('name');
            $table->text('slug');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('years_of_experience')->nullable();
            $table->decimal('fee')->default(0.00);
            $table->string('image')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('show_homepage')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('facilities');
    }
};
