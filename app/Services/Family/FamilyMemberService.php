<?php

namespace App\Services\Family;

use App\Exceptions\FamilyLimitExceededException;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FamilyMemberService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected SubscriptionService $subscriptionService
    ) {}

    public function addMember(
        Model $actor,
        Family $family,
        User $user,
        string $role = 'member',
        ?string $relationship = null
    ): FamilyMember {
        $owner = User::find($family->owner_user_id);
        if ($owner) {
            $this->subscriptionService->checkFamilyMemberLimit($owner, $family);
        } else {
            $currentCount = FamilyMember::where('family_id', $family->family_id)->count();
            if ($currentCount >= $family->max_members) {
                throw FamilyLimitExceededException::maxMembersReached($family->max_members);
            }
        }

        return DB::transaction(function () use ($actor, $family, $user, $role, $relationship) {
            $member = FamilyMember::create([
                'family_id' => $family->family_id,
                'user_id' => $user->user_id,
                'role' => $role,
                'relationship' => $relationship ?? 'member',
                'joined_at' => now(),
                'status_id' => 1,
            ]);

            \App\Events\FamilyMemberAdded::dispatch($member);

            $this->auditLogService->log(
                $actor,
                'family.member_added',
                FamilyMember::class,
                (string) $member->family_member_id,
                null,
                [
                    'family_id' => $family->family_id,
                    'added_user_id' => $user->user_id,
                    'role' => $role,
                ]
            );

            return $member;
        });
    }

    public function removeMember(Model $actor, FamilyMember $member): void
    {
        if ($member->role === 'owner') {
            throw new \InvalidArgumentException('Family owner cannot be removed from family. Delete family instead.');
        }

        DB::transaction(function () use ($actor, $member) {
            $familyId = $member->family_id;
            $userId = $member->user_id;
            $memberData = $member->toArray();
            $member->delete();

            \App\Events\FamilyMemberRemoved::dispatch($familyId, $userId);

            $this->auditLogService->log(
                $actor,
                'family.member_removed',
                FamilyMember::class,
                (string) $member->family_member_id,
                $memberData
            );
        });
    }

    public function changeRole(Model $actor, FamilyMember $member, string $newRole): FamilyMember
    {
        $oldRole = $member->role;
        $member->update(['role' => $newRole]);

        \App\Events\FamilyMemberRoleChanged::dispatch($member, $oldRole, $newRole);

        $this->auditLogService->log(
            $actor,
            'family.member_role_changed',
            FamilyMember::class,
            (string) $member->family_member_id,
            ['role' => $oldRole],
            ['role' => $newRole]
        );

        return $member;
    }
}
