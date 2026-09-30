<?php

namespace App\Events;

use App\Models\Subscription\UserSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelled implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UserSubscription $subscription
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->subscription->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'subscription.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'subscription_id' => $this->subscription->subscription_id,
            'user_id' => $this->subscription->user_id,
            'status' => is_object($this->subscription->status) ? $this->subscription->status->value : $this->subscription->status,
            'cancelled_at' => $this->subscription->cancelled_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}
