<?php

namespace App\Exceptions;

use Exception;

class DeviceAccessDeniedException extends Exception
{
    public static function notAssigned(string $deviceId): self
    {
        return new self("The specified device [{$deviceId}] is not assigned or authorized for your account/family context.");
    }

    public static function inactive(string $deviceId): self
    {
        return new self("Device [{$deviceId}] is currently inactive or decommissioned.");
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 403);
    }
}
