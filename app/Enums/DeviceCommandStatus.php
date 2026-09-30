<?php

namespace App\Enums;

enum DeviceCommandStatus: string
{
    case Pending   = 'pending';
    case Sent      = 'sent';
    case Delivered = 'delivered';
    case Executed  = 'executed';
    case Failed    = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Sent      => 'Sent',
            self::Delivered => 'Delivered',
            self::Executed  => 'Executed',
            self::Failed    => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Executed, self::Failed, self::Cancelled]);
    }
}
