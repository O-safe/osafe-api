<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\Family\FamilyMember;
use App\Models\Notification\Alert;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class AlertPolicy
{
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
            return $actor->hasPermissionTo('view alerts', 'admin');
        }
        return true;
    }

    public function view(Model $actor, Alert $alert): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view alerts', 'admin');
        }

        if ($actor instanceof User) {
            if ($alert->user_id === $actor->user_id) {
                return true;
            }

            if ($alert->device_id) {
                $device = Device::find($alert->device_id);
                return $device ? $this->userHasAccessToDevice($actor, $device) : false;
            }
        }

        return false;
    }

    public function resolve(Model $actor, Alert $alert): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('manage alerts', 'admin');
        }

        if ($actor instanceof User) {
            if ($alert->user_id === $actor->user_id) {
                return true;
            }

            if ($alert->device_id) {
                $device = Device::find($alert->device_id);
                return $device ? $this->userHasAccessToDevice($actor, $device) : false;
            }
        }

        return false;
    }

    protected function userHasAccessToDevice(User $user, Device $device): bool
    {
        $isDirect = $device->assignments()
            ->where('user_id', $user->user_id)
            ->where('status', 'active')
            ->exists();

        if ($isDirect || $device->registered_by === $user->user_id) {
            return true;
        }

        $familyIds = FamilyMember::where('user_id', $user->user_id)->pluck('family_id');
        if ($familyIds->isNotEmpty()) {
            return $device->assignments()
                ->whereIn('family_id', $familyIds)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }
}
