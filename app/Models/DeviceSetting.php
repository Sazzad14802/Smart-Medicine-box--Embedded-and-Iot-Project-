<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSetting extends Model
{
    protected $fillable = [
        'esp32_ip_address',
        'operating_mode',
        'missed_dose_timeout_minutes',
    ];

    protected $casts = [
        'missed_dose_timeout_minutes' => 'integer',
    ];
}
