<?php

namespace App\Http\Resources\Family;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'invitation_id' => $this->invitation_id,
            'family_id' => $this->family_id,
            'invited_by' => $this->invited_by,
            'invitee_email' => $this->invitee_email,
            'invitee_phone' => $this->invitee_phone,
            'role' => $this->role,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
