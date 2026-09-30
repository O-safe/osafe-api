<?php

namespace App\Events;

use App\Models\Device\Device;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("device.{$this->device->device_id}"),
            new PrivateChannel('admin.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->device->device_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'is_activated' => $this->device->is_activated,
            'changed_at' => now()->toIso8601String(),
        ];
    }
}
