<?php

namespace App\Enums;

enum BillingTransactionStatus: string
{
    case Pending    = 'pending';
    case Successful = 'successful';
    case Failed     = 'failed';
    case Reversed   = 'reversed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::Successful => 'Successful',
            self::Failed     => 'Failed',
            self::Reversed   => 'Reversed',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Successful;
    }
}
