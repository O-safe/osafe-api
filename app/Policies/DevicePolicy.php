<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class DevicePolicy
{
    /**
     * Super Admin bypass for Staff actors.
     */
    public function before(Model $actor, string $ability): ?bool
    {
        if ($actor instanceof Staff && $actor->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view devices', 'admin');
        }
        return true; // Users can list their own/assigned devices
    }

    public function view(Model $actor, Device $device): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view devices', 'admin');
        }

        if ($actor instanceof User) {
            return $this->userHasAccessToDevice($actor, $device);
        }

        return false;
    }

    public function create(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('create devices', 'admin');
        }
        return true; // Customer users can register devices under their account
    }

    public function update(Model $actor, Device $device): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('update devices', 'admin');
        }

        if ($actor instanceof User) {
            return $this->userHasAccessToDevice($actor, $device);
        }

        return false;
    }

    public function delete(Model $actor, Device $device): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('delete devices', 'admin');
        }
        return false; // Customer users cannot hard-delete physical device records
    }

    public function sendCommand(Model $actor, Device $device, string $commandType = 'locate_now'): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('send device commands', 'admin');
        }

        if ($actor instanceof User) {
            if (!$this->userHasAccessToDevice($actor, $device)) {
                return false;
            }

            // Destructive commands require Family Owner / Admin status
            if ($commandType === 'wipe_device') {
                return $this->isFamilyOwnerOrAdmin($actor, $device);
            }

            return true;
        }

        return false;
    }

    /**
     * Check if user is directly assigned to device or belongs to the family that owns the assignment.
     */
    protected function userHasAccessToDevice(User $user, Device $device): bool
    {
        // 1. Direct active assignment
        $isDirectlyAssigned = $device->assignments()
            ->where('user_id', $user->user_id)
            ->where('status', 'active')
            ->exists();

        if ($isDirectlyAssigned) {
            return true;
        }

        // 2. Registered by user
        if ($device->registered_by === $user->user_id) {
            return true;
        }

        // 3. Assigned to a family where user is an active member
        $familyIds = FamilyMember::where('user_id', $user->user_id)
            ->pluck('family_id');

        if ($familyIds->isNotEmpty()) {
            $isFamilyAssigned = $device->assignments()
                ->whereIn('family_id', $familyIds)
                ->where('status', 'active')
                ->exists();

            if ($isFamilyAssigned) {
                return true;
            }
        }

        return false;
    }

    protected function isFamilyOwnerOrAdmin(User $user, Device $device): bool
    {
        $familyIds = FamilyMember::where('user_id', $user->user_id)
            ->whereIn('role', ['owner', 'admin', 'guardian'])
            ->pluck('family_id');

        if ($familyIds->isEmpty()) {
            return false;
        }

        return $device->assignments()
            ->whereIn('family_id', $familyIds)
            ->where('status', 'active')
            ->exists();
    }
}
