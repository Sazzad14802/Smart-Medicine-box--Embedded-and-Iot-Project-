@extends('layouts.app')

@section('title', 'Dose Mode - Smart Medicine Box')
@section('page-title', 'Dose Mode')

@section('content')
    <div class="max-w-4xl">
        @if (session('success'))
            <div class="mb-6 rounded-md bg-green-100 border border-green-200 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-md bg-red-100 border border-red-200 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-100 border border-red-200 px-4 py-3 text-sm font-medium text-red-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-sm text-slate-500 mb-6">
            Set a reminder time for each of the 6 fixed compartments. Disabled compartments will
            not trigger a reminder or LED on the device.
        </p>

        <form action="{{ route('dose-mode.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Compartment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reminder Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Enabled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($schedules as $schedule)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-500">
                                    {{ $schedule->compartment_number }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                    {{ $schedule->compartment_label }}
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="time"
                                        name="schedules[{{ $schedule->compartment_number }}][reminder_time]"
                                        value="{{ substr($schedule->reminder_time, 0, 5) }}"
                                        class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                                        required
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <label class="relative inline-block h-6 w-11 cursor-pointer align-middle">
                                        <input
                                            type="checkbox"
                                            name="schedules[{{ $schedule->compartment_number }}][is_enabled]"
                                            value="1"
                                            class="peer sr-only"
                                            {{ $schedule->is_enabled ? 'checked' : '' }}
                                        >
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-emerald-600"></span>
                                        <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"></span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button
                type="submit"
                class="mt-6 inline-flex items-center rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
            >
                Save &amp; Send to ESP32
            </button>
        </form>
    </div>
@endsection
