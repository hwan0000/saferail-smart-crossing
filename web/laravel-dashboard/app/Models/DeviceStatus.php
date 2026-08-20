<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceStatus extends Model
{
    protected $fillable = [
        'device_name',
        'state',
        'actuator_voltage',
        'mode',
        'changed_at',
    ];

    protected $casts = [
        'actuator_voltage' => 'float',
        'changed_at'       => 'datetime',
    ];
}
