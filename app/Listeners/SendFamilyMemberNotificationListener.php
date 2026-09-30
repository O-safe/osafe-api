<?php

namespace App\Listeners;

use App\Events\FamilyMemberAdded;
use App\Events\FamilyMemberRemoved;
use App\Events\FamilyMemberRoleChanged;
use App\Jobs\SendNotificationJob;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendFamilyMemberNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(FamilyMemberAdded|FamilyMemberRemoved|FamilyMemberRoleChanged $event): void
    {
        $userId = match (true) {
            $event instanceof FamilyMemberAdded => $event->member->user_id,
            $event instanceof FamilyMemberRemoved => $event->removedUserId,
            $event instanceof FamilyMemberRoleChanged => $event->member->user_id,
        };

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $title = 'Family Group Update';
        $body = match (true) {
            $event instanceof FamilyMemberAdded => 'You have been added to a family group.',
            $event instanceof FamilyMemberRemoved => 'You have been removed from a family group.',
            $event instanceof FamilyMemberRoleChanged => "Your family role was updated to {$event->newRole}.",
        };

        $notification = $this->notificationService->createAndDeliver(
            user: $user,
            notificationType: 'family_member',
            title: $title,
            body: $body,
            channel: 'in_app'
        );

        if ($notification) {
            SendNotificationJob::dispatch($notification->notification_id);
        }
    }
}
