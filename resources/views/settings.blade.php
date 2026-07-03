@extends('layouts.app')

@section('title', 'Settings - Smart Medicine Box')
@section('page-title', 'Settings')

@section('content')
    <div class="max-w-xl">
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

        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <label for="esp32_ip_address" class="block text-sm font-medium text-slate-700">ESP32 IP Address</label>
                    <input
                        type="text"
                        id="esp32_ip_address"
                        name="esp32_ip_address"
                        value="{{ old('esp32_ip_address', $deviceSettings->esp32_ip_address ?? '') }}"
                        placeholder="192.168.1.100"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                        required
                    >
                    <p class="mt-1 text-xs text-slate-500">The local network IP address of the ESP32 device.</p>
                </div>

                <div>
                    <label for="missed_dose_timeout_minutes" class="block text-sm font-medium text-slate-700">
                        Missed-Dose Timeout (minutes)
                    </label>
                    <input
                        type="number"
                        id="missed_dose_timeout_minutes"
                        name="missed_dose_timeout_minutes"
                        value="{{ old('missed_dose_timeout_minutes', $deviceSettings->missed_dose_timeout_minutes ?? '') }}"
                        min="1"
                        max="60"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-emerald-500"
                        required
                    >
                    <p class="mt-1 text-xs text-slate-500">
                        How long after a scheduled dose before it's marked as missed (1–60 minutes).
                    </p>
                </div>
            </div>

            <button
                type="submit"
                class="mt-6 inline-flex items-center rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
            >
                Save Settings
            </button>
        </form>
    </div>
@endsection
