<?php

namespace App\Enums;

enum DeviceCommandType: string
{
    case LocateNow = 'locate_now';
    case SoundAlarm = 'sound_alarm';
    case RestartDevice = 'restart_device';
    case SyncData = 'sync_data';
    case LockDevice = 'lock_device';
    case UnlockDevice = 'unlock_device';
    case TakePhoto = 'take_photo';
    case RecordAudio = 'record_audio';
    case UpdateSettings = 'update_settings';
    case WipeDevice = 'wipe_device';

    public function isNormal(): bool
    {
        return in_array($this, [
            self::LocateNow,
            self::SoundAlarm,
            self::RestartDevice,
            self::SyncData,
        ]);
    }

    public function isSensitive(): bool
    {
        return in_array($this, [
            self::LockDevice,
            self::UnlockDevice,
            self::TakePhoto,
            self::RecordAudio,
            self::UpdateSettings,
        ]);
    }

    public function isDestructive(): bool
    {
        return $this === self::WipeDevice;
    }
}
