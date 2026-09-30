<?php

namespace App\Http\Resources\Alert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'preference_id' => $this->preference_id,
            'user_id' => $this->user_id,
            'notification_type' => $this->notification_type,
            'channel' => $this->channel,
            'enabled' => (bool) $this->enabled,
            'quiet_hours_enabled' => (bool) $this->quiet_hours_enabled,
            'quiet_from' => $this->quiet_from,
            'quiet_until' => $this->quiet_until,
        ];
    }
}
