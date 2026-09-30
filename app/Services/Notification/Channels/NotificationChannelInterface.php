<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification\OsafeNotification;
use App\Models\User\User;

interface NotificationChannelInterface
{
    public function send(OsafeNotification $notification, User $recipient): array;
}
