<?php

namespace App\Events;

use App\Models\Device\DeviceCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceCommandCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DeviceCommand $command
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("device.{$this->command->device_id}"),
        ];

        if ($this->command->issued_by_type === 'user') {
            $channels[] = new PrivateChannel("user.{$this->command->issued_by}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'device.command_created';
    }

    public function broadcastWith(): array
    {
        return [
            'command_id' => $this->command->command_id,
            'device_id' => $this->command->device_id,
            'command_type' => is_object($this->command->command_type) ? $this->command->command_type->value : $this->command->command_type,
            'status' => is_object($this->command->status) ? $this->command->status->value : $this->command->status,
            'issued_by_type' => $this->command->issued_by_type,
            'issued_by' => $this->command->issued_by,
            'created_at' => $this->command->created_at?->toIso8601String(),
        ];
    }
}
