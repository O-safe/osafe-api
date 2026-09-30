<?php

namespace App\Http\Resources\Device;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceCommandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'command_id' => $this->command_id,
            'device_id' => $this->device_id,
            'issued_by' => $this->issued_by,
            'issued_by_type' => $this->issued_by_type,
            'command_type' => $this->command_type?->value ?? $this->command_type,
            'status' => $this->status?->value ?? $this->status,
            'payload' => $this->payload,
            'response_data' => $this->response_data,
            'executed_at' => $this->executed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'logs' => $this->whenLoaded('logs', fn () => DeviceCommandLogResource::collection($this->logs)),
        ];
    }
}
