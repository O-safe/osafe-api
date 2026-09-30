<?php

namespace App\Services\Integration;

use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceStatus;
use App\Events\DeviceBatteryUpdated;
use App\Events\DeviceCommandStatusChanged;
use App\Events\DeviceLocationUpdated;
use App\Events\DeviceStatusChanged;
use App\Exceptions\DeviceAccessDeniedException;
use App\Exceptions\UnauthorizedCommandException;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use App\Models\Device\DeviceCommandLog;
use App\Models\Device\DeviceNetwork;
use App\Models\Device\DeviceStatusHistory;
use App\Models\Location\DeviceLocation;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DeviceIntegrationIngestionService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function recordHeartbeat(Device $device, array $data, ?string $ipAddress = null): Device
    {
        return DB::transaction(function () use ($device, $data, $ipAddress) {
            $device->update([
                'is_online' => true,
                'last_seen_at' => now(),
                'last_ip_address' => $ipAddress ?? $data['ip_address'] ?? $device->last_ip_address,
                'battery_level' => isset($data['battery_level']) ? (float) $data['battery_level'] : $device->battery_level,
                'battery_status' => $data['battery_status'] ?? $device->battery_status,
            ]);

            if (isset($data['network']) && is_array($data['network'])) {
                $this->recordNetwork($device, $data['network']);
            }

            return $device;
        });
    }

    public function recordStatus(Device $device, string $statusValue, ?string $reason = null): Device
    {
        $newStatusEnum = DeviceStatus::tryFrom($statusValue);
        if (!$newStatusEnum) {
            throw new \InvalidArgumentException("Invalid device status value: {$statusValue}");
        }

        return DB::transaction(function () use ($device, $newStatusEnum, $reason) {
            $oldStatus = $device->status->value;

            $device->update([
                'status' => $newStatusEnum,
                'is_activated' => $newStatusEnum === DeviceStatus::Active ? true : $device->is_activated,
            ]);

            DeviceStatusHistory::create([
                'device_id' => $device->device_id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatusEnum->value,
                'changed_by' => (string) $device->device_id,
                'changed_by_type' => 'device',
                'reason' => $reason ?? 'Device self-reported status update.',
            ]);

            event(new DeviceStatusChanged($device, $oldStatus, $newStatusEnum->value));

            return $device;
        });
    }

    public function recordBattery(Device $device, float $level, ?string $batteryStatus = null): Device
    {
        if ($level < 0 || $level > 100) {
            throw new \InvalidArgumentException('Battery level must be between 0 and 100.');
        }

        return DB::transaction(function () use ($device, $level, $batteryStatus) {
            $device->update([
                'battery_level' => $level,
                'battery_status' => $batteryStatus ?? $device->battery_status,
                'last_seen_at' => now(),
            ]);

            event(new DeviceBatteryUpdated($device));

            return $device;
        });
    }

    public function recordNetwork(Device $device, array $data): DeviceNetwork
    {
        return DeviceNetwork::create([
            'device_id' => $device->device_id,
            'type' => $data['connection_type'] ?? $data['type'] ?? 'cellular',
            'carrier' => $data['operator'] ?? $data['carrier'] ?? null,
            'signal_strength' => isset($data['signal_strength']) ? (string) $data['signal_strength'] : null,
            'ip_address' => $data['ip_address'] ?? null,
            'recorded_at' => isset($data['recorded_at']) ? \Carbon\Carbon::parse($data['recorded_at']) : now(),
        ]);
    }

    public function ingestLocation(Device $device, array $data): DeviceLocation
    {
        if (isset($data['device_id']) && (int) $data['device_id'] !== (int) $device->device_id) {
            throw DeviceAccessDeniedException::inactive((string) $data['device_id']);
        }

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException('Coordinates out of range.');
        }

        return DB::transaction(function () use ($device, $data, $lat, $lng) {
            $currentAssignment = $device->currentAssignment;
            $userId = $currentAssignment ? $currentAssignment->user_id : null;

            $recordedAt = isset($data['recorded_at']) ? \Carbon\Carbon::parse($data['recorded_at']) : now();

            $location = DeviceLocation::create([
                'device_id' => $device->device_id,
                'user_id' => $userId,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => isset($data['accuracy']) ? (float) $data['accuracy'] : null,
                'altitude' => isset($data['altitude']) ? (float) $data['altitude'] : null,
                'speed' => isset($data['speed']) ? (float) $data['speed'] : null,
                'heading' => isset($data['heading']) ? (float) $data['heading'] : null,
                'source' => $data['source'] ?? 'gps',
                'is_mock' => isset($data['is_mock']) ? (bool) $data['is_mock'] : false,
                'address' => $data['address'] ?? null,
                'recorded_at' => $recordedAt,
                'received_at' => now(),
            ]);

            $device->update([
                'is_online' => true,
                'last_seen_at' => now(),
            ]);

            if ($userId) {
                event(new DeviceLocationUpdated($location, (string) $userId));
            }

            return $location;
        });
    }

    public function getPendingCommands(Device $device): Collection
    {
        return DeviceCommand::where('device_id', $device->device_id)
            ->whereIn('status', [DeviceCommandStatus::Pending, DeviceCommandStatus::Sent])
            ->latest('created_at')
            ->get();
    }

    public function acknowledgeCommand(
        Device $device,
        int $commandId,
        string $status,
        array $responsePayload = [],
        ?string $errorMessage = null
    ): DeviceCommand {
        $command = DeviceCommand::find($commandId);

        if (!$command) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Command [{$commandId}] not found.");
        }

        if ((int) $command->device_id !== (int) $device->device_id) {
            throw UnauthorizedCommandException::restricted('ack', 'Device is not authorized to acknowledge commands belonging to another physical device.');
        }

        $targetStatus = match(strtolower($status)) {
            'delivered' => DeviceCommandStatus::Delivered,
            'executed', 'success', 'completed' => DeviceCommandStatus::Executed,
            'failed', 'error' => DeviceCommandStatus::Failed,
            default => throw new \InvalidArgumentException("Invalid command acknowledgement status: {$status}"),
        };

        return DB::transaction(function () use ($device, $command, $targetStatus, $responsePayload, $errorMessage) {
            $oldStatus = is_object($command->status) ? $command->status->value : (string) $command->status;

            $updateData = ['status' => $targetStatus];
            if ($targetStatus === DeviceCommandStatus::Executed) {
                $updateData['executed_at'] = now();
            }
            $command->update($updateData);

            DeviceCommandLog::create([
                'command_id' => $command->command_id,
                'device_id' => $device->device_id,
                'event' => $targetStatus->value,
                'message' => $errorMessage ?? "Command status updated to {$targetStatus->value} by physical device.",
                'response_payload' => $responsePayload,
                'logged_at' => now(),
            ]);

            if ($command->command_type->isSensitive() || $command->command_type->isDestructive()) {
                $this->auditLogService->log(
                    $device,
                    'device.command_acknowledged',
                    DeviceCommand::class,
                    (string) $command->command_id,
                    ['status' => $oldStatus],
                    [
                        'status' => $targetStatus->value,
                        'device_id' => $device->device_id,
                        'command_type' => $command->command_type->value,
                    ]
                );
            }

            event(new DeviceCommandStatusChanged($command, $oldStatus, $targetStatus->value));

            return $command;
        });
    }
}
