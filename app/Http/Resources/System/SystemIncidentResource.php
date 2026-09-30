<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'incident_id' => $this->incident_id,
            'title' => $this->title,
            'severity' => $this->severity,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
