<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'distance_cm',
        'distance_exit_cm',
        'entry_side',
        'speed_cms',
        'event_type',
        'status',
        'recorded_at',
    ];

    protected $casts = [
        'distance_cm'      => 'float',
        'distance_exit_cm' => 'float',
        'speed_cms'        => 'float',
        'recorded_at'      => 'datetime',
    ];
}
