<?php

use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User private channel: user.{userId}
Broadcast::channel('user.{userId}', function ($user, $userId) {
    if ($user instanceof Staff) {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view users', 'admin');
    }
    if ($user instanceof User) {
        return (string) $user->user_id === (string) $userId;
    }
    return false;
});

// Family private channel: family.{familyId}
Broadcast::channel('family.{familyId}', function ($user, $familyId) {
    if ($user instanceof Staff) {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view families', 'admin');
    }

    if ($user instanceof User) {
        $family = Family::find($familyId);
        if (!$family) {
            return false;
        }

        if ($family->owner_user_id === $user->user_id) {
            return true;
        }

        return FamilyMember::where('family_id', $familyId)
            ->where('user_id', $user->user_id)
            ->where('status_id', 1)
            ->exists();
    }

    return false;
});

// Device private channel: device.{deviceId}
Broadcast::channel('device.{deviceId}', function ($user, $deviceId) {
    if ($user instanceof Staff) {
        return $user->hasRole('Super Admin') || $user->hasPermissionTo('view devices', 'admin');
    }

    if ($user instanceof User) {
        $device = Device::find($deviceId);
        if (!$device) {
            return false;
        }

        // 1. Registered owner
        if ($device->registered_by === $user->user_id) {
            return true;
        }

        // 2. Direct assignment
        $isDirect = $device->assignments()
            ->where('user_id', $user->user_id)
            ->where('status', 'active')
            ->exists();

        if ($isDirect) {
            return true;
        }

        // 3. Family assignment
        $familyIds = FamilyMember::where('user_id', $user->user_id)->pluck('family_id');
        if ($familyIds->isNotEmpty()) {
            return $device->assignments()
                ->whereIn('family_id', $familyIds)
                ->where('status', 'active')
                ->exists();
        }
    }

    return false;
});

// Admin dashboard private channel: admin.dashboard
Broadcast::channel('admin.dashboard', function ($user) {
    if ($user instanceof Staff) {
        return $user->hasRole('Super Admin') ||
            $user->hasPermissionTo('view devices', 'admin') ||
            $user->hasPermissionTo('view alerts', 'admin');
    }
    return false;
});

// Admin audit log private channel: admin.audit
Broadcast::channel('admin.audit', function ($user) {
    if ($user instanceof Staff) {
        return $user->hasRole('Super Admin') ||
            $user->hasPermissionTo('view audit logs', 'admin');
    }
    return false;
});
