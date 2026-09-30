<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class FamilyMemberPolicy
{
    public function before(Model $actor, string $ability): ?bool
    {
        if ($actor instanceof Staff && $actor->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    public function view(Model $actor, FamilyMember $member): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view families', 'admin');
        }

        if ($actor instanceof User) {
            if ($actor->user_id === $member->user_id) {
                return true;
            }
            return FamilyMember::where('family_id', $member->family_id)
                ->where('user_id', $actor->user_id)
                ->exists();
        }

        return false;
    }

    public function update(Model $actor, FamilyMember $member): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('manage families', 'admin');
        }

        if ($actor instanceof User) {
            $family = Family::find($member->family_id);
            if (!$family) {
                return false;
            }

            return $family->owner_user_id === $actor->user_id ||
                FamilyMember::where('family_id', $member->family_id)
                    ->where('user_id', $actor->user_id)
                    ->whereIn('role', ['owner', 'admin'])
                    ->exists();
        }

        return false;
    }

    public function remove(Model $actor, FamilyMember $member): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('manage families', 'admin');
        }

        if ($actor instanceof User) {
            // Self-leaving family is allowed (unless owner)
            if ($actor->user_id === $member->user_id && $member->role !== 'owner') {
                return true;
            }

            $family = Family::find($member->family_id);
            if (!$family) {
                return false;
            }

            return $family->owner_user_id === $actor->user_id ||
                FamilyMember::where('family_id', $member->family_id)
                    ->where('user_id', $actor->user_id)
                    ->whereIn('role', ['owner', 'admin'])
                    ->exists();
        }

        return false;
    }
}
