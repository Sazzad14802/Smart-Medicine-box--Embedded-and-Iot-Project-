@extends('layouts.app')

@section('title', 'Device Controls - Smart Medicine Box')
@section('page-title', 'Device Controls')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl">
        {{-- Sync Time --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900">Sync Time</h3>
            <p class="mt-1 text-sm text-slate-500">
                Force the ESP32 to re-sync its clock with the NTP server.
            </p>
            <button
                id="sync-time-btn"
                type="button"
                class="mt-4 inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                Sync Time
            </button>
            <div id="sync-time-result" class="mt-3 text-sm"></div>
        </div>

        {{-- Restart Device --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900">Restart ESP32</h3>
            <p class="mt-1 text-sm text-slate-500">
                Reboots the device. Any in-progress reminder cycle will be interrupted.
            </p>
            <button
                id="restart-device-btn"
                type="button"
                class="mt-4 inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                Restart ESP32
            </button>
            <div id="restart-device-result" class="mt-3 text-sm"></div>
        </div>

        {{-- Refresh Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900">Refresh Status</h3>
            <p class="mt-1 text-sm text-slate-500">
                Fetch the device's latest reported status on demand.
            </p>
            <button
                id="refresh-status-btn"
                type="button"
                class="mt-4 inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                Refresh Status
            </button>
            <dl id="refresh-status-result" class="mt-3 text-sm text-slate-700 space-y-1"></dl>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function postJson(url) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            }).then((response) => response.json());
        }

        function showMessage(el, success, message) {
            const color = success ? 'text-green-700' : 'text-red-700';
            el.innerHTML = `<span class="${color} font-medium">${message}</span>`;
        }

        // Sync Time
        document.getElementById('sync-time-btn').addEventListener('click', function () {
            const btn = this;
            const resultEl = document.getElementById('sync-time-result');

            btn.disabled = true;
            btn.textContent = 'Syncing...';
            resultEl.innerHTML = '';

            postJson('{{ route('device-controls.sync-time') }}')
                .then((data) => showMessage(resultEl, data.success, data.message))
                .catch(() => showMessage(resultEl, false, 'Request failed. Please try again.'))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Sync Time';
                });
        });

        // Restart Device
        document.getElementById('restart-device-btn').addEventListener('click', function () {
            if (!confirm('Are you sure you want to restart the ESP32 device?')) {
                return;
            }

            const btn = this;
            const resultEl = document.getElementById('restart-device-result');

            btn.disabled = true;
            btn.textContent = 'Restarting...';
            resultEl.innerHTML = '';

            postJson('{{ route('device-controls.restart') }}')
                .then((data) => showMessage(resultEl, data.success, data.message))
                .catch(() => showMessage(resultEl, false, 'Request failed. Please try again.'))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Restart ESP32';
                });
        });

        // Refresh Status
        document.getElementById('refresh-status-btn').addEventListener('click', function () {
            const btn = this;
            const resultEl = document.getElementById('refresh-status-result');

            btn.disabled = true;
            btn.textContent = 'Refreshing...';
            resultEl.innerHTML = '';

            fetch('{{ route('device-controls.refresh-status') }}', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        resultEl.innerHTML = '<span class="text-red-700 font-medium">Unable to fetch status.</span>';
                        return;
                    }

                    const status = data.status;
                    resultEl.innerHTML = Object.entries(status).map(([key, value]) => `
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">${key}</dt>
                            <dd class="font-medium text-slate-900">${value}</dd>
                        </div>
                    `).join('');
                })
                .catch(() => {
                    resultEl.innerHTML = '<span class="text-red-700 font-medium">Request failed. Please try again.</span>';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Refresh Status';
                });
        });
    </script>
@endpush
