<?php

namespace App\Http\Controllers;

use App\Models\DeviceSetting;
use App\Services\Esp32Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * Show the device settings form.
     */
    public function index(): View
    {
        $deviceSettings = DeviceSetting::first();

        return view('settings', [
            'deviceSettings' => $deviceSettings,
        ]);
    }

    /**
     * Save the ESP32 IP address and missed-dose timeout, then push the new timeout to the device.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'esp32_ip_address' => 'required|ip',
            'missed_dose_timeout_minutes' => 'required|integer|min:1|max:60',
        ]);

        $deviceSettings = DeviceSetting::first();
        if ($deviceSettings) {
            $deviceSettings->update($validated);
        } else {
            DeviceSetting::create($validated);
        }

        $result = $this->esp32Service->sendTimeout($validated['missed_dose_timeout_minutes']);

        if (! $result['success']) {
            return back()->with('error', 'Settings saved, but the ESP32 device could not be reached.');
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
