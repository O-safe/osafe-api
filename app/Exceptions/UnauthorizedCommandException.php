<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedCommandException extends Exception
{
    public static function restricted(string $commandType, string $reason): self
    {
        return new self("Command [{$commandType}] rejected: {$reason}");
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 403);
    }
}
