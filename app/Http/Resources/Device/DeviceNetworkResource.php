<?php

namespace App\Http\Resources\Device;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceNetworkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'network_id' => $this->network_id,
            'device_id' => $this->device_id,
            'type' => $this->type ?? $this->network_type,
            'carrier' => $this->carrier ?? $this->operator_name,
            'signal_strength' => $this->signal_strength,
            'ip_address' => $this->ip_address,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
