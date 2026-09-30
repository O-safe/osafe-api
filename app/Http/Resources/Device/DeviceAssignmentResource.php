<?php

namespace App\Http\Resources\Device;

use App\Http\Resources\Family\FamilyResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assignment_id' => $this->assignment_id,
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'family_id' => $this->family_id,
            'assigned_by' => $this->assigned_by,
            'assigned_by_type' => $this->assigned_by_type,
            'status' => $this->status?->value ?? $this->status,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revocation_reason' => $this->revocation_reason,
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),
            'family' => $this->whenLoaded('family', fn () => FamilyResource::make($this->family)),
        ];
    }
}
