<?php

namespace App\Enums;

enum GeofenceEventType: string
{
    case Entry = 'entry';
    case Exit  = 'exit';
    case Dwell = 'dwell';

    public function label(): string
    {
        return match($this) {
            self::Entry => 'Entered Zone',
            self::Exit  => 'Exited Zone',
            self::Dwell => 'Dwelling in Zone',
        };
    }
}
