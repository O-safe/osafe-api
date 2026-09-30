<?php

namespace App\Listeners;

use App\Events\DeviceStatusChanged;
use App\Jobs\SendNotificationJob;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDeviceStatusNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(DeviceStatusChanged $event): void
    {
        $device = $event->device;
        if (!$device->registered_by) {
            return;
        }

        $user = User::find($device->registered_by);
        if (!$user) {
            return;
        }

        $rendered = $this->notificationService->renderTemplate('device_status', [
            'device_name' => $device->name ?? 'Device',
            'status' => $event->newStatus,
        ], 'in_app');

        $notification = $this->notificationService->createAndDeliver(
            user: $user,
            notificationType: 'device_status',
            title: $rendered['title'],
            body: $rendered['body'],
            channel: 'in_app',
            metadata: [
                'device_id' => $device->device_id,
                'new_status' => $event->newStatus,
            ]
        );

        if ($notification) {
            SendNotificationJob::dispatch($notification->notification_id);
        }
    }
}
