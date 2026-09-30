<?php

namespace App\Services\Device;

use App\Enums\DeviceAssignmentStatus;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeviceAssignmentService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected SubscriptionService $subscriptionService
    ) {}

    public function assignToUser(Model $actor, Device $device, User $targetUser, ?string $notes = null): DeviceAssignment
    {
        $this->subscriptionService->checkDeviceLimit($targetUser);

        return DB::transaction(function () use ($actor, $device, $targetUser, $notes) {
            // Revoke active assignments for this device
            DeviceAssignment::where('device_id', $device->device_id)
                ->where('status', DeviceAssignmentStatus::Active->value)
                ->update([
                    'status' => DeviceAssignmentStatus::Revoked->value,
                    'revoked_at' => now(),
                    'revocation_reason' => 'Reassigned to new user.',
                ]);

            $assignment = DeviceAssignment::create([
                'device_id' => $device->device_id,
                'user_id' => $targetUser->user_id,
                'assigned_by' => $actor instanceof User ? $actor->user_id : $actor->staff_id,
                'assigned_by_type' => $actor instanceof User ? 'user' : 'staff',
                'status' => DeviceAssignmentStatus::Active,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            $this->auditLogService->log(
                $actor,
                'device.assigned_user',
                DeviceAssignment::class,
                (string) $assignment->assignment_id,
                null,
                $assignment->toArray()
            );

            return $assignment;
        });
    }

    public function assignToFamily(Model $actor, Device $device, Family $family, ?string $notes = null): DeviceAssignment
    {
        return DB::transaction(function () use ($actor, $device, $family, $notes) {
            DeviceAssignment::where('device_id', $device->device_id)
                ->where('status', DeviceAssignmentStatus::Active->value)
                ->update([
                    'status' => DeviceAssignmentStatus::Revoked->value,
                    'revoked_at' => now(),
                    'revocation_reason' => 'Reassigned to family.',
                ]);

            $assignment = DeviceAssignment::create([
                'device_id' => $device->device_id,
                'family_id' => $family->family_id,
                'user_id' => null,
                'assigned_by' => $actor instanceof User ? $actor->user_id : $actor->staff_id,
                'assigned_by_type' => $actor instanceof User ? 'user' : 'staff',
                'status' => DeviceAssignmentStatus::Active,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            $this->auditLogService->log(
                $actor,
                'device.assigned_family',
                DeviceAssignment::class,
                (string) $assignment->assignment_id,
                null,
                $assignment->toArray()
            );

            return $assignment;
        });
    }

    public function revokeAssignment(Model $actor, DeviceAssignment $assignment, ?string $reason = null): void
    {
        $assignment->update([
            'status' => DeviceAssignmentStatus::Revoked,
            'revoked_at' => now(),
            'revocation_reason' => $reason ?? 'Assignment revoked.',
        ]);

        $this->auditLogService->log(
            $actor,
            'device.assignment_revoked',
            DeviceAssignment::class,
            (string) $assignment->assignment_id
        );
    }
}
