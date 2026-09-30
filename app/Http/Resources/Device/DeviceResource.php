<?php

namespace App\Http\Resources\Device;

use App\Http\Resources\Geofence\GeofenceResource;
use App\Http\Resources\Location\DeviceLocationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'device_id' => $this->device_id,
            'serial_number' => $this->serial_number,
            'imei' => $this->imei,
            'mac_address' => $this->mac_address,
            'model' => $this->model,
            'manufacturer' => $this->manufacturer,
            'hardware_version' => $this->hardware_version,
            'firmware_version' => $this->firmware_version,
            'os_type' => $this->os_type,
            'os_version' => $this->os_version,
            'platform' => $this->platform,
            'name' => $this->name,
            'color' => $this->color,
            'connectivity' => $this->connectivity,
            'status' => $this->status?->value ?? $this->status,
            'is_activated' => (bool) $this->is_activated,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'battery_level' => $this->battery_level !== null ? (float) $this->battery_level : null,
            'battery_status' => $this->battery_status,
            'is_online' => (bool) $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'current_assignment' => $this->whenLoaded('currentAssignment', fn () => DeviceAssignmentResource::make($this->currentAssignment)),
            'latest_location' => $this->whenLoaded('latestLocation', fn () => DeviceLocationResource::make($this->latestLocation)),
            'geofences' => $this->whenLoaded('geofences', fn () => GeofenceResource::collection($this->geofences)),
        ];
    }
}
