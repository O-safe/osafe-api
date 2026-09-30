<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Family\FamilyMember;
use App\Models\Geofence\Geofence;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class GeofencePolicy
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
            return $actor->hasPermissionTo('view geofences', 'admin');
        }
        return true;
    }

    public function view(Model $actor, Geofence $geofence): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view geofences', 'admin');
        }

        if ($actor instanceof User) {
            if ($geofence->owner_user_id === $actor->user_id) {
                return true;
            }

            if ($geofence->family_id) {
                return FamilyMember::where('family_id', $geofence->family_id)
                    ->where('user_id', $actor->user_id)
                    ->exists();
            }
        }

        return false;
    }

    public function create(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('create geofences', 'admin');
        }
        return true;
    }

    public function update(Model $actor, Geofence $geofence): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('update geofences', 'admin');
        }

        if ($actor instanceof User) {
            if ($geofence->owner_user_id === $actor->user_id) {
                return true;
            }

            if ($geofence->family_id) {
                return FamilyMember::where('family_id', $geofence->family_id)
                    ->where('user_id', $actor->user_id)
                    ->whereIn('role', ['owner', 'admin'])
                    ->exists();
            }
        }

        return false;
    }

    public function delete(Model $actor, Geofence $geofence): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('delete geofences', 'admin');
        }

        if ($actor instanceof User) {
            return $geofence->owner_user_id === $actor->user_id;
        }

        return false;
    }
}
