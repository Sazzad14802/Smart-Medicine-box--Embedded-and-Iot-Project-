<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Medicine Box')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('scripts-head')
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 shrink-0 bg-slate-900 text-slate-200 flex flex-col">
            <div class="px-6 py-5 border-b border-slate-800">
                <h1 class="text-lg font-semibold text-white">Smart Medicine Box</h1>
                <p class="text-xs text-slate-400 mt-1">ESP32 Control Panel</p>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1">
                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'route' => 'dashboard'],
                        ['label' => 'Mode Selection', 'route' => 'mode-selection'],
                        ['label' => 'Dose Mode', 'route' => 'dose-mode'],
                        ['label' => 'Medicine Mode', 'route' => 'medicine-mode'],
                        ['label' => 'Settings', 'route' => 'settings'],
                        ['label' => 'Device Controls', 'route' => 'device-controls'],
                        ['label' => 'Live Status', 'route' => 'live-status'],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    @php
                        $isActive = Route::has($item['route']) && request()->routeIs($item['route']);
                        $href = Route::has($item['route']) ? route($item['route']) : '#';
                    @endphp
                    <a
                        href="{{ $href }}"
                        class="block rounded-md px-3 py-2 text-sm font-medium transition-colors
                            {{ $isActive
                                ? 'bg-emerald-600 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="px-6 py-4 border-t border-slate-800 text-xs text-slate-500">
                &copy; {{ date('Y') }} Smart Medicine Box
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col">
            <header class="bg-white border-b border-slate-200 px-8 py-4">
                <h2 class="text-xl font-semibold text-slate-900">@yield('page-title', 'Dashboard')</h2>
            </header>

            <main class="flex-1 p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
