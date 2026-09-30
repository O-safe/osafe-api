<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Unactivated   = 'unactivated';
    case Active        = 'active';
    case Inactive      = 'inactive';
    case Suspended     = 'suspended';
    case Decommissioned = 'decommissioned';

    public function label(): string
    {
        return match($this) {
            self::Unactivated    => 'Not Activated',
            self::Active         => 'Active',
            self::Inactive       => 'Inactive',
            self::Suspended      => 'Suspended',
            self::Decommissioned => 'Decommissioned',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
