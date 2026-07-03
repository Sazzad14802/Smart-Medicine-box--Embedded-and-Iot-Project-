<?php

namespace Database\Seeders;

use App\Models\DeviceSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DeviceSetting::updateOrCreate(
            ['id' => 1],
            [
                'esp32_ip_address' => '192.168.1.50',
                'operating_mode' => 'dose_mode',
                'missed_dose_timeout_minutes' => 10,
            ]
        );
    }
}
