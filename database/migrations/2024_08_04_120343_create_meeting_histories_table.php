<?php

use App\Models\User;
use Modules\Doctor\app\Models\Doctor;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meeting_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Doctor::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('meeting_id');
            $table->string('meeting_time');
            $table->string('duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_histories');
    }
};
