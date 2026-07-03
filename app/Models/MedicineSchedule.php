<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineSchedule extends Model
{
    protected $fillable = [
        'name',
        'compartments',
        'reminder_time',
        'is_enabled',
    ];

    protected $casts = [
        'compartments' => 'array',
        'is_enabled' => 'boolean',
    ];
}
