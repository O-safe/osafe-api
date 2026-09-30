<?php

namespace App\Services\Notification\Channels;

use App\Events\AlertCreated;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use Illuminate\Support\Facades\Broadcast;

class InAppNotificationChannel implements NotificationChannelInterface
{
    public function send(OsafeNotification $notification, User $recipient): array
    {
        $notification->update([
            'status' => 'sent',
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        return [
            'success' => true,
            'status' => 'sent',
            'channel' => 'in_app',
        ];
    }
}
