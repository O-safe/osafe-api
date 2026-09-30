<?php

namespace App\Exceptions;

use Exception;

class InvalidStateTransitionException extends Exception
{
    public static function invalid(string $from, string $to, string $entity = 'Entity'): self
    {
        return new self("Invalid {$entity} state transition from [{$from}] to [{$to}].");
    }
}
