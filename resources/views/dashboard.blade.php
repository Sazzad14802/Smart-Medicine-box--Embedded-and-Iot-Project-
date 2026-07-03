@extends('layouts.app')

@section('title', 'Dashboard - Smart Medicine Box')
@section('page-title', 'Dashboard')

@section('content')
    @php
        $connected = $status['connected'] ?? false;

        $modeLabels = [
            'dose_mode' => 'Dose Mode',
            'medicine_mode' => 'Medicine Mode',
        ];
        $modeLabel = $modeLabels[$deviceSettings->operating_mode ?? null] ?? 'Unknown';

        $deviceStatus = $status['status'] ?? 'Unknown';
        $statusColors = [
            'Ready' => 'bg-blue-100 text-blue-800',
            'Medicine Time' => 'bg-amber-100 text-amber-800',
            'Medicine Taken' => 'bg-green-100 text-green-800',
            'Missed Dose' => 'bg-red-100 text-red-800',
        ];
        $statusColor = $statusColors[$deviceStatus] ?? 'bg-slate-100 text-slate-800';
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Connection Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <p class="text-sm font-medium text-slate-500">ESP32 Connection</p>
            <div class="mt-3">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold
                    {{ $connected ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    <span class="h-2 w-2 rounded-full {{ $connected ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    {{ $connected ? 'Connected' : 'Disconnected' }}
                </span>
            </div>
        </div>

        {{-- Operating Mode --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <p class="text-sm font-medium text-slate-500">Operating Mode</p>
            <p class="mt-3 text-lg font-semibold text-slate-900">{{ $modeLabel }}</p>
        </div>

        {{-- Missed Dose Timeout --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <p class="text-sm font-medium text-slate-500">Missed-Dose Timeout</p>
            <p class="mt-3 text-lg font-semibold text-slate-900">
                {{ $deviceSettings->missed_dose_timeout_minutes ?? '—' }} min
            </p>
        </div>

        {{-- Device Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <p class="text-sm font-medium text-slate-500">Device Status</p>
            <div class="mt-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $statusColor }}">
                    {{ $deviceStatus }}
                </span>
            </div>
        </div>
    </div>

    {{-- Test Connection --}}
    <div class="mt-8 bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl">
        <h3 class="text-base font-semibold text-slate-900">Connection Test</h3>
        <p class="mt-1 text-sm text-slate-500">
            Ping the ESP32 device on the local network to confirm it is reachable.
        </p>

        <button
            id="test-connection-btn"
            type="button"
            class="mt-4 inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"
        >
            Test Connection
        </button>

        <div id="test-connection-result" class="mt-4 text-sm"></div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('test-connection-btn').addEventListener('click', function () {
            const btn = this;
            const resultEl = document.getElementById('test-connection-result');

            btn.disabled = true;
            btn.textContent = 'Testing...';
            resultEl.innerHTML = '';

            fetch('{{ route('esp32.test-connection') }}', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((data) => {
                    const color = data.success ? 'text-green-700' : 'text-red-700';
                    resultEl.innerHTML = `<span class="${color} font-medium">${data.message}</span>`;
                })
                .catch(() => {
                    resultEl.innerHTML = '<span class="text-red-700 font-medium">Request failed. Please try again.</span>';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Test Connection';
                });
        });
    </script>
@endpush
