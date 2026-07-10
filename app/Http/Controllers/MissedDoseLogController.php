<?php

namespace App\Http\Controllers;

use App\Models\MissedDoseLog;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MissedDoseLogController extends Controller
{
    public function index(): View
    {
        $logs = MissedDoseLog::orderBy('logged_at', 'desc')->paginate(20);

        return view('missed-doses', compact('logs'));
    }

    public function clear(): RedirectResponse
    {
        MissedDoseLog::truncate();
        return back()->with('success', 'Missed dose logs have been cleared.');
    }
}
