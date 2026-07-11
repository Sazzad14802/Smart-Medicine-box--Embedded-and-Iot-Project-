@php
    $selectedCompartments = old('compartments', $schedule->compartments ?? []);
    $isEnabled = old('is_enabled', $schedule->is_enabled ?? true);
    $timeValue = old('reminder_time', isset($schedule) ? substr($schedule->reminder_time, 0, 5) : '');
@endphp

<div class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Schedule Name</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $schedule->name ?? '') }}"
            placeholder="e.g. Paracetamol 500mg"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
            required
        >
    </div>

    <div>
        <span class="block text-sm font-medium text-slate-700">Compartments</span>
        <p class="text-xs text-slate-500 mt-0.5">Select every compartment this medicine should be dispensed from.</p>
        <div class="mt-2 grid grid-cols-3 sm:grid-cols-6 gap-3">
            @for ($i = 1; $i <= 6; $i++)
                <label class="flex items-center justify-center rounded-md border border-slate-300 py-2 text-sm font-medium cursor-pointer has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700">
                    <input type="checkbox" name="compartments[]" value="{{ $i }}" class="sr-only"
                        {{ in_array($i, $selectedCompartments) ? 'checked' : '' }}>
                    {{ $i }}
                </label>
            @endfor
        </div>
    </div>

    <div>
        <label for="reminder_time" class="block text-sm font-medium text-slate-700">Reminder Time</label>
        <input
            type="time"
            id="reminder_time"
            name="reminder_time"
            value="{{ $timeValue }}"
            class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
            required
        >
    </div>

    <div>
        <span class="block text-sm font-medium text-slate-700 mb-2">Enabled</span>
        <label class="relative inline-block h-6 w-11 cursor-pointer align-middle">
            <input type="checkbox" name="is_enabled" value="1" class="peer sr-only" {{ $isEnabled ? 'checked' : '' }}>
            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-emerald-600"></span>
            <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"></span>
        </label>
    </div>
</div>
