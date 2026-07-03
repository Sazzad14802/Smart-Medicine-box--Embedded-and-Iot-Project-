<?php

namespace App\Http\Controllers;

use App\Models\MedicineSchedule;
use App\Services\Esp32Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineModeController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * List all medicine schedules.
     */
    public function index(): View
    {
        $schedules = MedicineSchedule::orderBy('name')->get();

        return view('medicine-mode.index', [
            'schedules' => $schedules,
        ]);
    }

    /**
     * Show the create schedule form.
     */
    public function create(): View
    {
        return view('medicine-mode.create');
    }

    /**
     * Validate and save a new medicine schedule.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSchedule($request);

        MedicineSchedule::create([
            'name' => $validated['name'],
            'compartments' => $validated['compartments'],
            'reminder_time' => $validated['reminder_time'],
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()->route('medicine-mode')->with('success', 'Medicine schedule created.');
    }

    /**
     * Show the edit form pre-filled with an existing schedule.
     */
    public function edit($id): View
    {
        $schedule = MedicineSchedule::findOrFail($id);

        return view('medicine-mode.edit', [
            'schedule' => $schedule,
        ]);
    }

    /**
     * Validate and update an existing medicine schedule.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $schedule = MedicineSchedule::findOrFail($id);
        $validated = $this->validateSchedule($request);

        $schedule->update([
            'name' => $validated['name'],
            'compartments' => $validated['compartments'],
            'reminder_time' => $validated['reminder_time'],
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()->route('medicine-mode')->with('success', 'Medicine schedule updated.');
    }

    /**
     * Delete a medicine schedule.
     */
    public function destroy($id): RedirectResponse
    {
        MedicineSchedule::findOrFail($id)->delete();

        return redirect()->route('medicine-mode')->with('success', 'Medicine schedule deleted.');
    }

    /**
     * Push all medicine schedules to the ESP32.
     */
    public function sync(): RedirectResponse
    {
        $schedules = MedicineSchedule::all()->toArray();
        $result = $this->esp32Service->sendMedicineSchedules($schedules);

        if (! $result['success']) {
            return back()->with('error', 'Unable to reach the ESP32 device.');
        }

        return back()->with('success', 'All medicine schedules sent to the ESP32.');
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function validateSchedule(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'compartments' => 'required|array|min:1',
            'compartments.*' => 'integer|between:1,6',
            'reminder_time' => 'required|date_format:H:i',
            'is_enabled' => 'nullable|boolean',
        ]);
    }
}
