<?php

namespace App\Exceptions;

use Exception;

class SubscriptionLimitExceededException extends Exception
{
    public static function maxDevicesReached(int $max): self
    {
        return new self("Subscription plan limit reached: maximum allowed devices is {$max}. Upgrade plan to add more devices.");
    }

    public static function expired(): self
    {
        return new self("Active subscription required to perform this action.");
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }
}
