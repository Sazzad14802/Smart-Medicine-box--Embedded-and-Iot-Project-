@extends('layouts.app')

@section('title', 'Live Status - Smart Medicine Box')
@section('page-title', 'Live Status')

@section('content')
    @php
        $modeLabels = [
            'dose_mode' => 'Dose Mode',
            'medicine_mode' => 'Medicine Mode',
        ];
    @endphp

    <div class="max-w-3xl">
        <div
            id="status-card"
            class="rounded-xl border-2 p-10 text-center transition-colors bg-blue-50 border-blue-300"
        >
            <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Device Status</p>
            <p id="status-text" class="mt-3 text-4xl font-bold text-blue-800">
                {{ $status['status'] ?? 'Unknown' }}
            </p>
            <p id="status-updated" class="mt-3 text-xs text-slate-400">Updated just now</p>
        </div>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                <p class="text-sm font-medium text-slate-500">Connection</p>
                <div class="mt-3">
                    <span id="connection-badge" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold bg-green-100 text-green-800">
                        <span id="connection-dot" class="h-2 w-2 rounded-full bg-green-500"></span>
                        <span id="connection-text">{{ ($status['connected'] ?? false) ? 'Connected' : 'Disconnected' }}</span>
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                <p class="text-sm font-medium text-slate-500">Operating Mode</p>
                <p id="mode-text" class="mt-3 text-lg font-semibold text-slate-900">
                    {{ $modeLabels[$status['mode'] ?? null] ?? 'Unknown' }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
                <p class="text-sm font-medium text-slate-500">Missed-Dose Timeout</p>
                <p id="timeout-text" class="mt-3 text-lg font-semibold text-slate-900">
                    {{ $status['missed_dose_timeout'] ?? '—' }} min
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const pollUrl = '{{ route('live-status.poll') }}';
        const modeLabels = { dose_mode: 'Dose Mode', medicine_mode: 'Medicine Mode' };

        const statusStyles = {
            'Ready': { card: 'bg-blue-50 border-blue-300', text: 'text-blue-800', pulse: false },
            'Medicine Time': { card: 'bg-amber-50 border-amber-300', text: 'text-amber-800', pulse: true },
            'Medicine Taken': { card: 'bg-green-50 border-green-300', text: 'text-green-800', pulse: false },
            'Missed Dose': { card: 'bg-red-50 border-red-300', text: 'text-red-800', pulse: false },
        };

        const notifications = {
            'Medicine Time': { title: '💊 Medicine Time!', body: "It's time to take your medicine." },
            'Medicine Taken': { title: '✅ Medicine Taken', body: 'Great! Medicine has been taken.' },
            'Missed Dose': { title: '⚠️ Missed Dose', body: 'A dose was missed. Please check.' },
        };

        let previousStatus = document.getElementById('status-text').textContent.trim();

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        function notify(statusKey) {
            const config = notifications[statusKey];
            if (!config) return;
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(config.title, { body: config.body });
            }
        }

        function applyStatusStyles(statusKey) {
            const style = statusStyles[statusKey] ?? { card: 'bg-slate-50 border-slate-300', text: 'text-slate-800', pulse: false };
            const card = document.getElementById('status-card');
            const text = document.getElementById('status-text');

            card.className = `rounded-xl border-2 p-10 text-center transition-colors ${style.card} ${style.pulse ? 'animate-pulse' : ''}`;
            text.className = `mt-3 text-4xl font-bold ${style.text}`;
            text.textContent = statusKey;
        }

        function updateUI(data) {
            applyStatusStyles(data.status);

            document.getElementById('connection-text').textContent = data.connected ? 'Connected' : 'Disconnected';
            document.getElementById('connection-badge').className = `inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold ${data.connected ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            document.getElementById('connection-dot').className = `h-2 w-2 rounded-full ${data.connected ? 'bg-green-500' : 'bg-red-500'}`;

            document.getElementById('mode-text').textContent = modeLabels[data.mode] ?? 'Unknown';
            document.getElementById('timeout-text').textContent = `${data.missed_dose_timeout} min`;

            document.getElementById('status-updated').textContent = 'Updated ' + new Date().toLocaleTimeString();
        }

        function poll() {
            fetch(pollUrl, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data) => {
                    updateUI(data);

                    if (data.status !== previousStatus) {
                        notify(data.status);
                        previousStatus = data.status;
                    }
                })
                .catch(() => {
                    document.getElementById('status-updated').textContent = 'Update failed — will retry';
                });
        }

        setInterval(poll, 1000);
    </script>
@endpush
