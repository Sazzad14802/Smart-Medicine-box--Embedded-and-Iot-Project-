<?php

namespace Database\Seeders;

use App\Models\DoseSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DoseScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $compartments = [
            1 => ['label' => 'Before Breakfast', 'time' => '07:00:00'],
            2 => ['label' => 'After Breakfast', 'time' => '08:00:00'],
            3 => ['label' => 'Before Lunch', 'time' => '12:00:00'],
            4 => ['label' => 'After Lunch', 'time' => '13:00:00'],
            5 => ['label' => 'Before Dinner', 'time' => '19:00:00'],
            6 => ['label' => 'After Dinner', 'time' => '20:00:00'],
        ];

        foreach ($compartments as $number => $details) {
            DoseSchedule::updateOrCreate(
                ['compartment_number' => $number],
                [
                    'compartment_label' => $details['label'],
                    'reminder_time' => $details['time'],
                    'is_enabled' => true,
                ]
            );
        }
    }
}
