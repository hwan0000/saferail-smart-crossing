<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_train_detected')->default(false); // Ada kereta atau tidak
            $table->string('crossing_status')->default('safe');   // safe | warning | danger
            $table->float('confidence')->default(0);              // Tingkat keyakinan model (0-100%)
            $table->float('distance_km')->nullable();             // Jarak kereta terdekat (km)
            $table->float('approach_speed_kmh')->nullable();      // Kecepatan pendekatan (km/h)
            $table->timestamp('predicted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_predictions');
    }
};
