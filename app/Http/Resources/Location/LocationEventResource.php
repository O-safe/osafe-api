<?php

namespace App\Http\Resources\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->event_id,
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'event_type' => $this->event_type,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'details' => $this->details,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
