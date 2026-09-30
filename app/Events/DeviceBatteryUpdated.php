<?php

namespace App\Events;

use App\Models\Device\Device;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceBatteryUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Device $device
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("device.{$this->device->device_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.battery_updated';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->device->device_id,
            'battery_level' => $this->device->battery_level,
            'battery_status' => $this->device->battery_status,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
