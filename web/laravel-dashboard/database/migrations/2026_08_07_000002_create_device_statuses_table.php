<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');              // barrier_gate | traffic_signal
            $table->string('state');                    // barrier: open/closed | signal: red/yellow/green
            $table->float('actuator_voltage')->nullable(); // Tegangan aktuator (V), khusus barrier
            $table->string('mode')->default('auto');    // auto | manual
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_statuses');
    }
};
