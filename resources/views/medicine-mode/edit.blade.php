@extends('layouts.app')

@section('title', 'Edit Medicine Schedule - Smart Medicine Box')
@section('page-title', 'Edit Medicine Schedule')

@section('content')
    <div class="max-w-2xl">
        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-100 border border-red-200 px-4 py-3 text-sm font-medium text-red-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('medicine-mode.update', $schedule->id) }}" method="POST">
            @csrf
            @method('PUT')

            @include('medicine-mode._form')

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                >
                    Update Schedule
                </button>
                <a href="{{ route('medicine-mode') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
