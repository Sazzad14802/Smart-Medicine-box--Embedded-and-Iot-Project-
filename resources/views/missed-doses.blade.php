@extends('layouts.app')

@section('title', 'Missed Doses - Smart Medicine Box')
@section('page-title', 'Missed Doses')

@section('content')
    <div class="max-w-4xl">
        @if (session('success'))
            <div class="mb-6 rounded-md bg-green-100 border border-green-200 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-slate-500 max-w-xl">
                A history of missed doses. The device temporarily stores missed doses and synchronizes them here when you open the web application.
            </p>

            @if($logs->count() > 0)
                <form action="{{ route('missed-doses.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all missed dose logs?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-md border border-red-600 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Clear Logs
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date & Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Operating Mode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Scheduled Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Missed Compartments</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                {{ $log->logged_at->format('M d, Y h:i A') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if($log->operating_mode == 'dose_mode')
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Dose Mode</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">Medicine Mode</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-red-600">
                                {{ $log->scheduled_time }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if($log->missed_compartments)
                                    @foreach(explode(',', $log->missed_compartments) as $comp)
                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-100 text-xs font-semibold text-red-800 mr-1">
                                            {{ trim($comp) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-slate-400 italic">Unknown</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                No missed doses recorded. Great job!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
