<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCancelled;
use App\Jobs\SendNotificationJob;
use App\Models\User\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSubscriptionNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(SubscriptionActivated|SubscriptionCancelled $event): void
    {
        $subscription = $event->subscription;
        $user = User::find($subscription->user_id);
        if (!$user) {
            return;
        }

        $title = $event instanceof SubscriptionActivated ? 'Subscription Activated' : 'Subscription Cancelled';
        $body = $event instanceof SubscriptionActivated
            ? 'Your O SAFE subscription plan has been successfully activated.'
            : 'Your O SAFE subscription plan has been cancelled.';

        $notification = $this->notificationService->createAndDeliver(
            user: $user,
            notificationType: 'subscription',
            title: $title,
            body: $body,
            channel: 'in_app',
            metadata: [
                'subscription_id' => $subscription->subscription_id,
                'status' => is_object($subscription->status) ? $subscription->status->value : $subscription->status,
            ]
        );

        if ($notification) {
            SendNotificationJob::dispatch($notification->notification_id);
        }
    }
}
