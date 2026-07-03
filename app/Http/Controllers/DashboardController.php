<?php

namespace App\Http\Controllers;

use App\Models\DeviceSetting;
use App\Services\Esp32Service;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * Show the dashboard with device settings and live ESP32 status.
     */
    public function index(): View
    {
        $deviceSettings = DeviceSetting::first();
        $status = $this->esp32Service->getStatus();

        return view('dashboard', [
            'deviceSettings' => $deviceSettings,
            'status' => $status,
        ]);
    }

    /**
     * AJAX endpoint: ping the ESP32 and report reachability as JSON.
     */
    public function testConnection(): JsonResponse
    {
        $connected = $this->esp32Service->testConnection();

        return response()->json([
            'success' => $connected,
            'message' => $connected
                ? 'ESP32 device is reachable.'
                : 'Unable to reach ESP32 device.',
        ]);
    }
}
