<?php

namespace App\Http\Resources\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'location_id' => $this->location_id,
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'accuracy' => $this->accuracy !== null ? (float) $this->accuracy : null,
            'altitude' => $this->altitude !== null ? (float) $this->altitude : null,
            'speed' => $this->speed !== null ? (float) $this->speed : null,
            'heading' => $this->heading !== null ? (float) $this->heading : null,
            'source' => $this->source,
            'is_mock' => (bool) $this->is_mock,
            'address' => $this->address,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
