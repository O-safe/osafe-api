<?php

namespace App\Http\Resources\Alert;

use App\Http\Resources\Device\DeviceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'alert_id' => $this->alert_id,
            'user_id' => $this->user_id,
            'device_id' => $this->device_id,
            'geofence_id' => $this->geofence_id,
            'type' => $this->type,
            'severity' => $this->severity?->value ?? $this->severity,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status?->value ?? $this->status,
            'is_read' => (bool) $this->is_read,
            'is_resolved' => (bool) $this->is_resolved,
            'resolved_by' => $this->resolved_by,
            'read_at' => $this->read_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'triggered_at' => $this->triggered_at?->toIso8601String(),
            'device' => $this->whenLoaded('device', fn () => DeviceResource::make($this->device)),
        ];
    }
}
