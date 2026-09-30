<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class FamilyPolicy
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
            return $actor->hasPermissionTo('view families', 'admin');
        }
        return true;
    }

    public function view(Model $actor, Family $family): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view families', 'admin');
        }

        if ($actor instanceof User) {
            return $family->owner_user_id === $actor->user_id ||
                FamilyMember::where('family_id', $family->family_id)
                    ->where('user_id', $actor->user_id)
                    ->exists();
        }

        return false;
    }

    public function create(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('create families', 'admin');
        }
        return true;
    }

    public function update(Model $actor, Family $family): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('update families', 'admin');
        }

        if ($actor instanceof User) {
            return $family->owner_user_id === $actor->user_id ||
                FamilyMember::where('family_id', $family->family_id)
                    ->where('user_id', $actor->user_id)
                    ->whereIn('role', ['owner', 'admin'])
                    ->exists();
        }

        return false;
    }

    public function delete(Model $actor, Family $family): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('delete families', 'admin');
        }

        if ($actor instanceof User) {
            return $family->owner_user_id === $actor->user_id;
        }

        return false;
    }

    public function manageMembers(Model $actor, Family $family): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('manage families', 'admin');
        }

        if ($actor instanceof User) {
            return $family->owner_user_id === $actor->user_id ||
                FamilyMember::where('family_id', $family->family_id)
                    ->where('user_id', $actor->user_id)
                    ->whereIn('role', ['owner', 'admin'])
                    ->exists();
        }

        return false;
    }
}
