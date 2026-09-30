<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'subscription_id' => $this->subscription_id,
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status?->value ?? $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'auto_renew' => (bool) $this->auto_renew,
            'created_at' => $this->created_at?->toIso8601String(),
            'plan' => $this->whenLoaded('plan', fn () => SubscriptionPlanResource::make($this->plan)),
        ];
    }
}
