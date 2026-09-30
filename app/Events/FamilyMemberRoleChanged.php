<?php

namespace App\Events;

use App\Models\Family\FamilyMember;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FamilyMemberRoleChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FamilyMember $member,
        public string $oldRole,
        public string $newRole
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("family.{$this->member->family_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'family.member_role_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'family_id' => $this->member->family_id,
            'user_id' => $this->member->user_id,
            'old_role' => $this->oldRole,
            'new_role' => $this->newRole,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
