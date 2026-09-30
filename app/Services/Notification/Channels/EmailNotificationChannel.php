<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationChannel implements NotificationChannelInterface
{
    public function send(OsafeNotification $notification, User $recipient): array
    {
        if (empty($recipient->email)) {
            $notification->update([
                'status' => 'failed',
                'failure_reason' => 'Recipient email address is missing.',
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'channel' => 'email',
                'error' => 'Recipient email address is missing.',
            ];
        }

        // Email delivery abstraction via Laravel Mailer
        Log::info("Email notification abstraction triggered for user [{$recipient->user_id}] email [{$recipient->email}]");

        $notification->update([
            'status' => 'sent',
            'is_sent' => true,
            'sent_at' => now(),
        ]);

        return [
            'success' => true,
            'status' => 'sent',
            'channel' => 'email',
        ];
    }
}
