<?php

namespace App\Events;

use App\Models\Notification\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alert $alert,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->alert->user_id) {
            $channels[] = new PrivateChannel("user.{$this->alert->user_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'alert.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alert->alert_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'is_read' => $this->alert->is_read,
            'is_resolved' => $this->alert->is_resolved,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
