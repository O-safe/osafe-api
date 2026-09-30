<?php

namespace App\Events;

use App\Models\Geofence\GeofenceEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GeofenceViolation implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GeofenceEvent $geofenceEvent,
        public ?int $familyId = null,
        public ?string $userId = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.dashboard'),
        ];

        if ($this->userId) {
            $channels[] = new PrivateChannel("user.{$this->userId}");
        }

        if ($this->familyId) {
            $channels[] = new PrivateChannel("family.{$this->familyId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'geofence.violation';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->geofenceEvent->event_id,
            'geofence_id' => $this->geofenceEvent->geofence_id,
            'device_id' => $this->geofenceEvent->device_id,
            'event_type' => 'violation',
            'severity' => 'critical',
            'event_time' => $this->geofenceEvent->event_time?->toIso8601String(),
        ];
    }
}
