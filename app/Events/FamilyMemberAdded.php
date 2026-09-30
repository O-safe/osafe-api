<?php

namespace App\Events;

use App\Models\Family\FamilyMember;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FamilyMemberAdded implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FamilyMember $member
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("family.{$this->member->family_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'family.member_added';
    }

    public function broadcastWith(): array
    {
        return [
            'family_id' => $this->member->family_id,
            'user_id' => $this->member->user_id,
            'role' => $this->member->role,
            'relationship' => $this->member->relationship,
            'joined_at' => $this->member->joined_at?->toIso8601String(),
        ];
    }
}
