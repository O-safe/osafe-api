<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Unread    = 'unread';
    case Read      = 'read';
    case Dismissed = 'dismissed';
    case Resolved  = 'resolved';

    public function label(): string
    {
        return match($this) {
            self::Unread    => 'Unread',
            self::Read      => 'Read',
            self::Dismissed => 'Dismissed',
            self::Resolved  => 'Resolved',
        };
    }

    public function requiresAttention(): bool
    {
        return $this === self::Unread;
    }
}
