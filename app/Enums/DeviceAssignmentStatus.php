<?php

namespace App\Enums;

enum DeviceAssignmentStatus: string
{
    case Active     = 'active';
    case Revoked    = 'revoked';
    case Transferred = 'transferred';

    public function label(): string
    {
        return match($this) {
            self::Active      => 'Active',
            self::Revoked     => 'Revoked',
            self::Transferred => 'Transferred',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
