<?php

namespace App\Http\Resources\Family;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'family_member_id' => $this->family_member_id,
            'family_id' => $this->family_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'relationship' => $this->relationship,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),
        ];
    }
}
