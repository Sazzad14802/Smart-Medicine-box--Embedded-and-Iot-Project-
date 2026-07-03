@extends('layouts.app')

@section('title', 'Medicine Mode - Smart Medicine Box')
@section('page-title', 'Medicine Mode')

@section('content')
    <div class="max-w-5xl">
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

        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-slate-500 max-w-xl">
                Manage individually scheduled medicines. Each schedule can span multiple
                compartments and has its own reminder time.
            </p>

            <div class="flex items-center gap-3 shrink-0">
                <a
                    href="{{ route('medicine-mode.create') }}"
                    class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                >
                    Add New Schedule
                </a>

                <form action="{{ route('medicine-mode.sync') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                    >
                        Save All to ESP32
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Compartments</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $schedule->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @foreach ($schedule->compartments as $compartment)
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-slate-100 text-xs font-semibold text-slate-700 mr-1">
                                        {{ $compartment }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ substr($schedule->reminder_time, 0, 5) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $schedule->is_enabled ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $schedule->is_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('medicine-mode.edit', $schedule->id) }}" class="font-medium text-emerald-600 hover:text-emerald-800">
                                    Edit
                                </a>
                                <form
                                    action="{{ route('medicine-mode.destroy', $schedule->id) }}"
                                    method="POST"
                                    class="inline"
                                    onsubmit="return confirm('Delete this medicine schedule?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-3 font-medium text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                No medicine schedules yet. Click "Add New Schedule" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
