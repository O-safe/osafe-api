<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_id' => $this->report_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'type' => $this->type,
            'file_path' => $this->file_path,
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
