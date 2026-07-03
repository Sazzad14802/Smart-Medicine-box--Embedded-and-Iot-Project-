<?php

namespace App\Http\Controllers;

use App\Services\Esp32Service;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LiveStatusController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * Show the live status page.
     */
    public function index(): View
    {
        return view('live-status', [
            'status' => $this->esp32Service->getStatus(),
        ]);
    }

    /**
     * AJAX endpoint: fetch the latest device status, polled on an interval by the view.
     */
    public function poll(): JsonResponse
    {
        return response()->json($this->esp32Service->getStatus());
    }
}
