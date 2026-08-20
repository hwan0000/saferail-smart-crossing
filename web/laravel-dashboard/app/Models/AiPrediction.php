<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrediction extends Model
{
    protected $fillable = [
        'is_train_detected',
        'crossing_status',
        'confidence',
        'distance_km',
        'approach_speed_kmh',
        'predicted_at',
    ];

    protected $casts = [
        'is_train_detected'  => 'boolean',
        'confidence'         => 'float',
        'distance_km'        => 'float',
        'approach_speed_kmh' => 'float',
        'predicted_at'       => 'datetime',
    ];
}
