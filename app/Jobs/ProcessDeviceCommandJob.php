<?php

namespace App\Jobs;

use App\Enums\DeviceCommandStatus;
use App\Events\DeviceCommandStatusChanged;
use App\Models\Device\DeviceCommand;
use App\Models\Device\DeviceCommandLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDeviceCommandJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];
    public int $timeout = 120;

    public function __construct(
        public string $commandId
    ) {}

    public function handle(): void
    {
        $command = DeviceCommand::find($this->commandId);
        if (!$command) {
            return;
        }

        if ($command->status === DeviceCommandStatus::Executed || $command->status === DeviceCommandStatus::Failed) {
            return;
        }

        $oldStatus = is_object($command->status) ? $command->status->value : (string) $command->status;

        // Transition command state to Processing -> Delivered for backend processing
        $command->update([
            'status' => DeviceCommandStatus::Sent,
            'sent_at' => now(),
        ]);

        DeviceCommandLog::create([
            'command_id' => $command->command_id,
            'device_id' => $command->device_id,
            'event' => 'sent',
            'message' => 'Command queued and delivered to backend processing engine.',
            'response_payload' => ['message' => 'Command queued and delivered to backend processing engine.'],
            'logged_at' => now(),
        ]);

        event(new DeviceCommandStatusChanged($command, $oldStatus, DeviceCommandStatus::Sent->value));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessDeviceCommandJob failed for command [{$this->commandId}]: " . $exception->getMessage());

        $command = DeviceCommand::find($this->commandId);
        if ($command) {
            $oldStatus = is_object($command->status) ? $command->status->value : (string) $command->status;
            $command->update(['status' => DeviceCommandStatus::Failed]);

            DeviceCommandLog::create([
                'command_id' => $command->command_id,
                'device_id' => $command->device_id,
                'event' => 'failed',
                'message' => 'Backend processing failed.',
                'response_payload' => ['error' => 'Backend processing failed.'],
                'logged_at' => now(),
            ]);

            event(new DeviceCommandStatusChanged($command, $oldStatus, DeviceCommandStatus::Failed->value));
        }
    }
}
