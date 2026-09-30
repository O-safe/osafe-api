<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Device\DeviceResource;
use App\Http\Resources\Family\FamilyMemberResource;
use App\Http\Resources\Subscription\UserSubscriptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'home_address' => $this->home_address,
            'date_of_birth' => $this->date_of_birth,
            'passport' => $this->passport,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'family_memberships' => $this->whenLoaded('familyMembers', fn () => FamilyMemberResource::collection($this->familyMembers)),
            'assigned_devices' => $this->whenLoaded('devices', fn () => DeviceResource::collection($this->devices)),
            'active_subscription' => $this->whenLoaded('activeSubscription', fn () => UserSubscriptionResource::make($this->activeSubscription)),
        ];
    }
}
