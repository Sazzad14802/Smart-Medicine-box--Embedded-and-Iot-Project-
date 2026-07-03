<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class Esp32ConnectionException extends Exception
{
    public function __construct(string $message = 'Unable to reach the ESP32 device.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
