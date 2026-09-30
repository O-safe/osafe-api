<?php

namespace App\Events;

use App\Models\Location\DeviceLocation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceLocationUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DeviceLocation $location,
        public string $userId
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("device.{$this->location->device_id}"),
        ];

        if ($this->userId) {
            $channels[] = new PrivateChannel("user.{$this->userId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'device.location_updated';
    }

    public function broadcastWith(): array
    {
        return [
            'location_id' => $this->location->location_id,
            'device_id' => $this->location->device_id,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'accuracy' => $this->location->accuracy ? (float) $this->location->accuracy : null,
            'speed' => $this->location->speed ? (float) $this->location->speed : null,
            'recorded_at' => $this->location->recorded_at?->toIso8601String(),
        ];
    }
}
