<?php

namespace App\Http\Resources\Geofence;

use App\Http\Resources\Device\DeviceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeofenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'geofence_id' => $this->geofence_id,
            'owner_user_id' => $this->owner_user_id,
            'family_id' => $this->family_id,
            'name' => $this->name,
            'description' => $this->description,
            'center_latitude' => (float) $this->center_latitude,
            'center_longitude' => (float) $this->center_longitude,
            'radius_meters' => (int) $this->radius_meters,
            'shape' => $this->shape,
            'alert_on_entry' => (bool) $this->alert_on_entry,
            'alert_on_exit' => (bool) $this->alert_on_exit,
            'is_active' => (bool) $this->is_active,
            'color' => $this->color,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'devices' => $this->whenLoaded('devices', fn () => DeviceResource::collection($this->devices)),
        ];
    }
}
