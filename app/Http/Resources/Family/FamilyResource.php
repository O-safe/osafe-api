<?php

namespace App\Http\Resources\Family;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'family_id' => $this->family_id,
            'name' => $this->name,
            'description' => $this->description,
            'avatar' => $this->avatar,
            'invite_code' => $this->invite_code,
            'max_members' => $this->max_members,
            'owner_user_id' => $this->owner_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'owner' => $this->whenLoaded('owner', fn () => UserResource::make($this->owner)),
            'members' => $this->whenLoaded('members', fn () => FamilyMemberResource::collection($this->members)),
        ];
    }
}
