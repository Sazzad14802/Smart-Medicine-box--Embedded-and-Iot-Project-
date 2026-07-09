<?php

namespace App\Http\Controllers;

use App\Models\DoseSchedule;
use App\Services\Esp32Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoseModeController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * Show all 6 compartment dose schedules.
     */
    public function index(): View
    {
        $schedules = DoseSchedule::orderBy('compartment_number')->get();

        return view('dose-mode', [
            'schedules' => $schedules,
        ]);
    }

    /**
     * Save all 6 dose schedules at once and push them to the ESP32.
     */
    public function update(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'schedules' => 'required|array|size:6',
        'schedules.*.reminder_time' => 'required|date_format:H:i',
        'schedules.*.is_enabled' => 'nullable|boolean',
    ]);

    foreach ($validated['schedules'] as $compartmentNumber => $data) {
        DoseSchedule::where('compartment_number', $compartmentNumber)->update([
            'reminder_time' => $data['reminder_time'],
            'is_enabled' => $request->boolean("schedules.{$compartmentNumber}.is_enabled"),
        ]);
    }

    $schedules = DoseSchedule::orderBy('compartment_number')
        ->get()
        ->map(fn($s) => [
            'compartment_number' => $s->compartment_number,
            'reminder_time'      => $s->reminder_time,
            'is_enabled'         => (bool) $s->is_enabled,
        ])
        ->all();

    $result = $this->esp32Service->sendDoseSchedules($schedules);

    if (! $result['success']) {
        return back()->with('error', 'Dose schedules saved, but the ESP32 device could not be reached.');
    }

    return back()->with('success', 'Dose schedules updated successfully.');
}
}
