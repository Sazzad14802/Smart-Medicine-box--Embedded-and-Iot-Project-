<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoseSchedule extends Model
{
    protected $fillable = [
        'compartment_number',
        'compartment_label',
        'reminder_time',
        'is_enabled',
    ];

    protected $casts = [
        'compartment_number' => 'integer',
        'is_enabled' => 'boolean',
    ];
}
