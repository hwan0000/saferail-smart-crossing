<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->float('distance_cm');               // Jarak objek dari sensor HC-SR04 (cm)
            $table->float('speed_cms')->default(0);     // Kecepatan pendekatan (cm/s)
            $table->string('event_type')->default('reading'); // approach_detected | departure_complete | proximity_alert | reading
            $table->string('status')->default('normal'); // normal | warning | danger
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
