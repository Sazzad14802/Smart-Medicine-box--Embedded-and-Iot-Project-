@extends('layouts.app')

@section('title', 'Mode Selection - Smart Medicine Box')
@section('page-title', 'Mode Selection')

@section('content')
    <div class="max-w-3xl">
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

        @error('mode')
            <div class="mb-6 rounded-md bg-red-100 border border-red-200 px-4 py-3 text-sm font-medium text-red-800">
                {{ $message }}
            </div>
        @enderror

        <p class="text-sm text-slate-500 mb-6">
            Choose how the device should operate. This determines whether reminders are tied to fixed daily
            compartments or to individually scheduled medicines.
        </p>

        <form action="{{ route('mode-selection.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- Dose Mode --}}
                <label class="relative block cursor-pointer">
                    <input
                        type="radio"
                        name="mode"
                        value="dose_mode"
                        class="peer sr-only"
                        {{ old('mode', $currentMode) === 'dose_mode' ? 'checked' : '' }}
                    >
                    <div class="rounded-lg border-2 border-slate-200 bg-white p-6 transition-colors
                        peer-checked:border-emerald-600 peer-checked:bg-emerald-50
                        hover:border-slate-300">
                        <div class="flex items-center justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M12 21a9 9 0 100-18 9 9 0 000 18z" />
                                </svg>
                            </div>
                            @if ($currentMode === 'dose_mode')
                                <span class="inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white">
                                    Active
                                </span>
                            @endif
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">Dose Mode</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Uses the 6 fixed compartments (Before/After Breakfast, Lunch, Dinner) with a reminder
                            time set per compartment. Best for a simple, repeating daily routine.
                        </p>
                    </div>
                </label>

                {{-- Medicine Mode --}}
                <label class="relative block cursor-pointer">
                    <input
                        type="radio"
                        name="mode"
                        value="medicine_mode"
                        class="peer sr-only"
                        {{ old('mode', $currentMode) === 'medicine_mode' ? 'checked' : '' }}
                    >
                    <div class="rounded-lg border-2 border-slate-200 bg-white p-6 transition-colors
                        peer-checked:border-emerald-600 peer-checked:bg-emerald-50
                        hover:border-slate-300">
                        <div class="flex items-center justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            @if ($currentMode === 'medicine_mode')
                                <span class="inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white">
                                    Active
                                </span>
                            @endif
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">Medicine Mode</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Assign individual medicines to one or more compartments, each with its own reminder
                            time. Best when different medicines follow different schedules.
                        </p>
                    </div>
                </label>
            </div>

            <button
                type="submit"
                class="mt-8 inline-flex items-center rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
            >
                Save Mode
            </button>
        </form>
    </div>
@endsection
