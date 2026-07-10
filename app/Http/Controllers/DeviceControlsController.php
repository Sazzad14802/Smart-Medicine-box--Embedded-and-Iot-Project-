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

        // Auto-sync timeout and mode from the database so the ESP32 recovers them after a reboot!
        $settings = \App\Models\DeviceSetting::first();
        if ($settings) {
            $this->esp32Service->sendTimeout($settings->missed_dose_timeout_minutes ?? 10);
            if ($settings->operating_mode) {
                $this->esp32Service->sendMode($settings->operating_mode);
            }
        }

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
