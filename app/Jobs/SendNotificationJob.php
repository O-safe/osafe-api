<?php

namespace App\Jobs;

use App\Models\Notification\OsafeNotification;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 15, 45];
    public int $timeout = 60;

    public function __construct(
        public int $notificationId
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notification = OsafeNotification::find($this->notificationId);
        if (!$notification) {
            return;
        }

        if ($notification->status === 'sent' || $notification->status === 'delivered') {
            return;
        }

        $notificationService->deliverChannel($notification);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendNotificationJob failed for notification [{$this->notificationId}]: " . $exception->getMessage());

        $notification = OsafeNotification::find($this->notificationId);
        if ($notification) {
            $notification->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);
        }
    }
}
