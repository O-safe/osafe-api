<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'plan_id' => $this->plan_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'max_devices' => (int) $this->max_devices,
            'max_family_members' => (int) $this->max_family_members,
            'location_history_days' => (int) $this->location_history_days,
            'price_monthly' => (float) $this->price_monthly,
            'price_yearly' => (float) $this->price_yearly,
            'currency' => $this->currency,
            'is_active' => (bool) $this->is_active,
            'is_featured' => (bool) $this->is_featured,
            'features' => $this->features,
        ];
    }
}
