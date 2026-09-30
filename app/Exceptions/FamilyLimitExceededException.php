<?php

namespace App\Exceptions;

use Exception;

class FamilyLimitExceededException extends Exception
{
    public static function maxMembersReached(int $max): self
    {
        return new self("Cannot add member: family has reached its limit of {$max} members.");
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }
}
