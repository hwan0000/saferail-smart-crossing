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
        Schema::table('sensor_readings', function (Blueprint $table) {
            // 'A' or 'B' -- which sonar the current/last crossing entered
            // from. Null when SAFE (no train currently crossing).
            $table->string('entry_side', 1)->nullable()->after('distance_exit_cm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropColumn('entry_side');
        });
    }
};
