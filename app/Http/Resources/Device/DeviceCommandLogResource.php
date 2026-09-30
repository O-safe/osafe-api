<?php

namespace App\Http\Resources\Device;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceCommandLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'log_id' => $this->log_id,
            'command_id' => $this->command_id,
            'event_type' => $this->event_type,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
