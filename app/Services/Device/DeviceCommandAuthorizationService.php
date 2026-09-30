<?php

namespace App\Services\Device;

use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceCommandType;
use App\Events\DeviceCommandCreated;
use App\Exceptions\DeviceAccessDeniedException;
use App\Exceptions\UnauthorizedCommandException;
use App\Jobs\ProcessDeviceCommandJob;
use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class DeviceCommandAuthorizationService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected SubscriptionService $subscriptionService
    ) {}

    public function authorizeAndQueueCommand(
        Model $actor,
        Device $device,
        string|DeviceCommandType $commandType,
        array $payload = []
    ): DeviceCommand {
        $typeEnum = $commandType instanceof DeviceCommandType
            ? $commandType
            : DeviceCommandType::tryFrom($commandType);

        if (!$typeEnum) {
            throw UnauthorizedCommandException::restricted((string) $commandType, 'Unknown or unsupported command type.');
        }

        // 1. Gate policy check
        if (!Gate::forUser($actor)->allows('sendCommand', [$device, $typeEnum->value])) {
            throw UnauthorizedCommandException::restricted($typeEnum->value, 'Actor is not authorized to execute this command on the specified device.');
        }

        // 2. Device state check
        if ($device->status->value === 'decommissioned' || $device->status->value === 'inactive') {
            throw DeviceAccessDeniedException::inactive((string) $device->device_id);
        }

        // 3. Subscription check for sensitive / destructive commands issued by customer users
        if ($actor instanceof User && ($typeEnum->isSensitive() || $typeEnum->isDestructive())) {
            $subscription = $this->subscriptionService->getActiveSubscription($actor);
            if (!$subscription) {
                throw UnauthorizedCommandException::restricted($typeEnum->value, 'Active subscription required for sensitive/destructive device commands.');
            }
        }

        // 4. Create Command record
        $actorId = $actor instanceof User ? $actor->user_id : $actor->staff_id;
        $actorType = $actor instanceof User ? 'user' : 'staff';

        $command = DeviceCommand::create([
            'device_id' => $device->device_id,
            'issued_by_type' => $actorType,
            'issued_by' => $actorId,
            'command_type' => $typeEnum,
            'status' => DeviceCommandStatus::Pending,
            'payload' => $payload,
        ]);

        // 5. Audit Log requirement for sensitive & destructive commands
        if ($typeEnum->isSensitive() || $typeEnum->isDestructive()) {
            $this->auditLogService->log(
                $actor,
                'device.command_queued',
                DeviceCommand::class,
                (string) $command->command_id,
                null,
                [
                    'device_id' => $device->device_id,
                    'command_type' => $typeEnum->value,
                    'is_destructive' => $typeEnum->isDestructive(),
                ]
            );
        }

        // 6. Broadcast event & Queue background processing job
        event(new DeviceCommandCreated($command));
        ProcessDeviceCommandJob::dispatch((string) $command->command_id);

        return $command;
    }
}
