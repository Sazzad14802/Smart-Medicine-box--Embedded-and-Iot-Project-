<?php

namespace App\Http\Controllers;

use App\Services\Esp32Service;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DeviceControlsController extends Controller
{
    public function __construct(protected Esp32Service $esp32Service)
    {
    }

    /**
     * Show the device controls page.
     */
    public function index(): View
    {
        return view('device-controls');
    }

    /**
     * AJAX endpoint: trigger an NTP time re-sync on the ESP32.
     */
    public function syncTime(): JsonResponse
    {
        $result = $this->esp32Service->syncTime();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Time sync requested successfully.'
                : ($result['error'] ?? 'Unable to reach the ESP32 device.'),
        ]);
    }

    /**
     * AJAX endpoint: ask the ESP32 to restart.
     */
    public function restartDevice(): JsonResponse
    {
        $result = $this->esp32Service->restartDevice();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Restart command sent successfully.'
                : ($result['error'] ?? 'Unable to reach the ESP32 device.'),
        ]);
    }

    /**
     * AJAX endpoint: fetch the latest device status.
     */
    public function refreshStatus(): JsonResponse
    {
        $status = $this->esp32Service->getStatus();

        return response()->json([
            'success' => $status['success'] ?? true,
            'status' => $status,
        ]);
    }
}
