<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemHealthCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'check_id' => $this->check_id,
            'service_name' => $this->service_name,
            'status' => $this->status,
            'response_time_ms' => $this->response_time_ms,
            'message' => $this->message,
            'checked_at' => $this->checked_at?->toIso8601String(),
        ];
    }
}
