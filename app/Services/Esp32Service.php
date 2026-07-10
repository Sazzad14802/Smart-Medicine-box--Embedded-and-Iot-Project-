<?php

namespace App\Services;

use App\Exceptions\Esp32ConnectionException;
use App\Models\DeviceSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Esp32Service
{
    /**
     * Base URL of the ESP32's built-in HTTP server, read from device_settings.
     */
    protected function baseUrl(): string
    {
        $ip = DeviceSetting::first()?->esp32_ip_address ?? '192.168.1.50';

        return "http://{$ip}";
    }

    /**
     * Get the current status of the ESP32 device.
     * Success shape (from the device): { connected, mode, status, missed_dose_timeout, success: true }
     */
    public function getStatus(): array
    {
        try {
            $response = $this->request('get', '/status');
            
            if (!empty($response['log_queue'])) {
                foreach ($response['log_queue'] as $logItem) {
                    $parts = explode('|', $logItem);
                    if (count($parts) >= 2) {
                        \App\Models\MissedDoseLog::create([
                            'operating_mode' => $parts[0],
                            'scheduled_time' => $parts[1],
                            'missed_compartments' => $parts[2] ?? null,
                        ]);
                    }
                }
                $this->clearLogs();
            }

            return $response;
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Ping the ESP32 to check it is reachable on the network.
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl()}/ping");

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('ESP32 connection test failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Switch the ESP32's operating mode.
     */
    public function sendMode(string $mode): array
    {
        try {
            return $this->request('post', '/set-mode', [
                'mode' => $mode,
            ]);
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Send dose mode schedules to the ESP32.
     */
    public function sendDoseSchedules(array $schedules): array
    {
        try {
            return $this->request('post', '/set-dose-schedules', [
                'schedules' => $schedules,
            ]);
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Send medicine mode schedules to the ESP32.
     */
    public function sendMedicineSchedules(array $schedules): array
    {
        try {
            return $this->request('post', '/set-medicine-schedules', [
                'schedules' => $schedules,
            ]);
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Update the missed-dose timeout on the ESP32.
     */
    public function sendTimeout(int $minutes): array
    {
        try {
            return $this->request('post', '/set-timeout', [
                'timeout_minutes' => $minutes,
            ]);
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Push the server's current time to the ESP32 (used in place of/alongside NTP sync).
     */
    public function syncTime(): array
    {
        try {
            return $this->request('post', '/sync-time', [
                'timestamp' => now()->timestamp,
                'datetime' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Ask the ESP32 to restart.
     */
    public function restartDevice(): array
    {
        try {
            return $this->request('post', '/restart');
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Ask the ESP32 to clear its log queue.
     */
    public function clearLogs(): array
    {
        try {
            return $this->request('post', '/clear-logs');
        } catch (Esp32ConnectionException $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Shared GET/POST helper for all outbound ESP32 requests.
     * Throws Esp32ConnectionException on network failure or a non-2xx response;
     * callers catch it and translate it into a structured error array.
     */
    protected function request(string $method, string $path, array $payload = []): array
    {
        $url = "{$this->baseUrl()}{$path}";

        try {
            $response = Http::timeout(5)->{$method}($url, $payload);
        } catch (Throwable $e) {
            throw new Esp32ConnectionException(
                "Unable to reach the ESP32 device at {$url}: {$e->getMessage()}",
                $e
            );
        }

        if ($response->failed()) {
            throw new Esp32ConnectionException(
                "ESP32 device at {$url} responded with an error (HTTP {$response->status()})."
            );
        }

        return array_merge(['success' => true], $response->json() ?? []);
    }

    /**
     * Convert a caught Esp32ConnectionException into the structured error array
     * every public method returns on failure, logging the underlying cause.
     */
    protected function errorResponse(Esp32ConnectionException $e): array
    {
        Log::warning($e->getMessage());

        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
