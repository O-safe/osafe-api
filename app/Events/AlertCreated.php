<?php

namespace App\Events;

use App\Models\Notification\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alert $alert
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.dashboard'),
        ];

        if ($this->alert->user_id) {
            $channels[] = new PrivateChannel("user.{$this->alert->user_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alert->alert_id,
            'user_id' => $this->alert->user_id,
            'device_id' => $this->alert->device_id,
            'title' => $this->alert->title,
            'severity' => is_object($this->alert->severity) ? $this->alert->severity->value : $this->alert->severity,
            'status' => is_object($this->alert->status) ? $this->alert->status->value : $this->alert->status,
            'triggered_at' => $this->alert->triggered_at?->toIso8601String(),
        ];
    }
}
