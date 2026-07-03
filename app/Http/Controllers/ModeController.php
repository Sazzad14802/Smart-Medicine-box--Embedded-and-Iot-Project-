<?php

namespace App\Http\Controllers;

use App\Models\DeviceSetting;
use App\Services\Esp32Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModeController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * Show the mode selection page.
     */
    public function index(): View
    {
        $deviceSettings = DeviceSetting::first();

        return view('mode', [
            'currentMode' => $deviceSettings->operating_mode ?? null,
        ]);
    }

    /**
     * Save the selected operating mode and push it to the ESP32.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:dose_mode,medicine_mode',
        ]);

        $deviceSettings = DeviceSetting::first();
        $deviceSettings->update(['operating_mode' => $validated['mode']]);

        $result = $this->esp32Service->sendMode($validated['mode']);

        if (! $result['success']) {
            return back()->with('error', 'Mode saved, but the ESP32 device could not be reached.');
        }

        return back()->with('success', 'Operating mode updated successfully.');
    }
}
