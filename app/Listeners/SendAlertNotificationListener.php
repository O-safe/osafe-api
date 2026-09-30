<?php

namespace App\Listeners;

use App\Events\AlertCreated;
use App\Jobs\SendNotificationJob;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAlertNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(AlertCreated $event): void
    {
        $alert = $event->alert;
        if (!$alert->user_id) {
            return;
        }

        $user = User::find($alert->user_id);
        if (!$user) {
            return;
        }

        $rendered = $this->notificationService->renderTemplate('alert', [
            'title' => $alert->title,
            'severity' => is_object($alert->severity) ? $alert->severity->value : (string) $alert->severity,
        ], 'in_app');

        $notification = $this->notificationService->createAndDeliver(
            user: $user,
            notificationType: 'alert',
            title: $rendered['title'],
            body: $rendered['body'],
            channel: 'in_app',
            alertId: $alert->alert_id,
            templateId: $rendered['template_id'],
            metadata: [
                'alert_id' => $alert->alert_id,
                'device_id' => $alert->device_id,
            ]
        );

        if ($notification) {
            SendNotificationJob::dispatch($notification->notification_id);
        }
    }
}
