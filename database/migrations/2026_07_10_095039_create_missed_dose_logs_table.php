<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('missed_dose_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operating_mode'); // 'dose_mode' or 'medicine_mode'
            $table->string('scheduled_time'); // '08:00'
            $table->string('missed_compartments')->nullable(); // '1,2,5'
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missed_dose_logs');
    }
};
