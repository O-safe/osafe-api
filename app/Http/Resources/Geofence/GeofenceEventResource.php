<?php

namespace App\Http\Resources\Geofence;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeofenceEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->event_id,
            'geofence_id' => $this->geofence_id,
            'device_id' => $this->device_id,
            'event_type' => $this->event_type?->value ?? $this->event_type,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'triggered_at' => $this->triggered_at?->toIso8601String(),
        ];
    }
}
