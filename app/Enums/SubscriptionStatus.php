<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending   = 'pending';
    case Active    = 'active';
    case Cancelled = 'cancelled';
    case Expired   = 'expired';
    case PastDue   = 'past_due';
    case Trialing  = 'trialing';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Active    => 'Active',
            self::Cancelled => 'Cancelled',
            self::Expired   => 'Expired',
            self::PastDue   => 'Past Due',
            self::Trialing  => 'Trial',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Trialing]);
    }
}
