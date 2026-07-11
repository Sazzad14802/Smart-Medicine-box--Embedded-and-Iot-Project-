<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissedDoseLog extends Model
{
    protected $fillable = [
        'operating_mode',
        'scheduled_time',
        'missed_compartments',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];
}
