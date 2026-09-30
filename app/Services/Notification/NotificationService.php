<?php

namespace App\Services\Notification;

use App\Models\Notification\NotificationPreference;
use App\Models\Notification\NotificationTemplate;
use App\Models\Notification\OsafeNotification;
use App\Models\User\User;
use App\Services\Notification\Channels\EmailNotificationChannel;
use App\Services\Notification\Channels\InAppNotificationChannel;
use App\Services\Notification\Channels\NotificationChannelInterface;
use App\Services\Notification\Channels\PushNotificationChannel;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected array $channelDrivers = [];

    public function __construct()
    {
        $this->channelDrivers = [
            'in_app' => new InAppNotificationChannel(),
            'email' => new EmailNotificationChannel(),
            'push' => new PushNotificationChannel(),
        ];
    }

    public function isPreferenceEnabled(User $user, string $notificationType, string $channel): bool
    {
        $pref = NotificationPreference::where('user_id', $user->user_id)
            ->where('notification_type', $notificationType)
            ->where('channel', $channel)
            ->first();

        if ($pref && !$pref->enabled) {
            return false;
        }

        if ($pref && $pref->quiet_hours_enabled && $pref->quiet_from && $pref->quiet_until) {
            $now = now()->format('H:i:s');
            if ($now >= $pref->quiet_from && $now <= $pref->quiet_until) {
                return false;
            }
        }

        return true;
    }

    public function renderTemplate(string $templateSlug, array $variables = [], string $channel = 'in_app'): array
    {
        $template = NotificationTemplate::where('slug', $templateSlug)
            ->where('is_active', true)
            ->first();

        $title = $template?->name ?? 'O SAFE Alert Notification';
        $subject = $template?->subject ?? 'O SAFE Notification';
        $body = $template?->body ?? 'An automated safety notification has been triggered.';

        // Strip sensitive keys from variables to prevent leakage
        $sensitiveKeys = ['password', 'password_hash', 'remember_token', 'mfa_secret', 'secret', 'payment_token', 'api_key_hash', 'token', 'credentials'];
        $safeVariables = [];
        foreach ($variables as $key => $val) {
            $isSensitive = false;
            foreach ($sensitiveKeys as $sKey) {
                if (str_contains(strtolower($key), $sKey)) {
                    $isSensitive = true;
                    break;
                }
            }
            if (!$isSensitive) {
                $safeVariables[$key] = is_scalar($val) ? (string) $val : json_encode($val);
            }
        }

        foreach ($safeVariables as $key => $val) {
            $body = str_replace('{{' . $key . '}}', $val, $body);
            $title = str_replace('{{' . $key . '}}', $val, $title);
            $subject = str_replace('{{' . $key . '}}', $val, $subject);
        }

        return [
            'template_id' => $template?->template_id,
            'title' => $title,
            'subject' => $subject,
            'body' => $body,
        ];
    }

    public function createAndDeliver(
        User $user,
        string $notificationType,
        string $title,
        string $body,
        string $channel = 'in_app',
        ?int $alertId = null,
        ?int $templateId = null,
        ?array $metadata = []
    ): ?OsafeNotification {
        // 1. Preference check
        if (!$this->isPreferenceEnabled($user, $notificationType, $channel)) {
            Log::info("Notification [{$notificationType}] on channel [{$channel}] suppressed by preference for user [{$user->user_id}].");
            return null;
        }

        // 2. Idempotency check: prevent duplicate notifications within 30 seconds
        $duplicate = OsafeNotification::where('user_id', $user->user_id)
            ->where('title', $title)
            ->where('channel', $channel)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();

        if ($duplicate) {
            Log::info("Duplicate notification suppressed for user [{$user->user_id}] title [{$title}].");
            return $duplicate;
        }

        // 3. Create persistent notification record
        return OsafeNotification::create([
            'user_id' => $user->user_id,
            'alert_id' => $alertId,
            'template_id' => $templateId,
            'channel' => $channel,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_sent' => false,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);
    }

    public function deliverChannel(OsafeNotification $notification): array
    {
        $user = $notification->user;
        if (!$user) {
            $notification->update([
                'status' => 'failed',
                'failure_reason' => 'Recipient user not found.',
            ]);
            return ['success' => false, 'status' => 'failed', 'error' => 'User not found'];
        }

        $driver = $this->channelDrivers[$notification->channel] ?? $this->channelDrivers['in_app'];
        return $driver->send($notification, $user);
    }
}
