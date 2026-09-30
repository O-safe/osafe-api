<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open         = 'open';
    case InProgress   = 'in_progress';
    case WaitingUser  = 'waiting_user';
    case Resolved     = 'resolved';
    case Closed       = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open        => 'Open',
            self::InProgress  => 'In Progress',
            self::WaitingUser => 'Waiting on User',
            self::Resolved    => 'Resolved',
            self::Closed      => 'Closed',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::WaitingUser]);
    }
}
