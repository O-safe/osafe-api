<?php

namespace App\Services\Device;

use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\Device\Device;
use App\Models\Device\DeviceStatusHistory;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeviceService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected SubscriptionService $subscriptionService
    ) {}

    public function registerDevice(User $user, array $data): Device
    {
        $this->subscriptionService->checkDeviceLimit($user);

        return DB::transaction(function () use ($user, $data) {
            $device = Device::create(array_merge($data, [
                'registered_by' => $user->user_id,
                'created_by' => $user->user_id,
                'status' => DeviceStatus::Unactivated,
                'is_activated' => false,
            ]));

            $this->auditLogService->log(
                $user,
                'device.registered',
                Device::class,
                (string) $device->device_id,
                null,
                $device->toArray()
            );

            return $device;
        });
    }

    public function activateDevice(Model $actor, Device $device): Device
    {
        return DB::transaction(function () use ($actor, $device) {
            $oldStatus = $device->status->value;

            $device->update([
                'status' => DeviceStatus::Active,
                'is_activated' => true,
                'activated_at' => now(),
            ]);

            DeviceStatusHistory::create([
                'device_id' => $device->device_id,
                'previous_status' => $oldStatus,
                'new_status' => DeviceStatus::Active->value,
                'changed_by' => $actor instanceof User ? $actor->user_id : $actor->staff_id,
                'changed_by_type' => $actor instanceof User ? 'user' : 'staff',
                'reason' => 'Device activation completed.',
            ]);

            $this->auditLogService->log(
                $actor,
                'device.activated',
                Device::class,
                (string) $device->device_id,
                ['status' => $oldStatus],
                ['status' => DeviceStatus::Active->value]
            );

            event(new DeviceStatusChanged($device, $oldStatus, DeviceStatus::Active->value));

            return $device;
        });
    }
}
