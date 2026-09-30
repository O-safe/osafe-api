<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FamilyMemberRemoved implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $familyId,
        public string $removedUserId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("family.{$this->familyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'family.member_removed';
    }

    public function broadcastWith(): array
    {
        return [
            'family_id' => $this->familyId,
            'removed_user_id' => $this->removedUserId,
            'removed_at' => now()->toIso8601String(),
        ];
    }
}
