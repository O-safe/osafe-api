<?php

namespace App\Services\Family;

use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FamilyService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected SubscriptionService $subscriptionService
    ) {}

    public function createFamily(User $owner, array $data): Family
    {
        return DB::transaction(function () use ($owner, $data) {
            $family = Family::create(array_merge($data, [
                'owner_user_id' => $owner->user_id,
                'created_by' => $owner->user_id,
                'max_members' => $data['max_members'] ?? 6,
            ]));

            // Automatically add owner as first FamilyMember with owner role
            $member = FamilyMember::create([
                'family_id' => $family->family_id,
                'user_id' => $owner->user_id,
                'role' => 'owner',
                'relationship' => 'owner',
                'joined_at' => now(),
                'status_id' => 1,
            ]);

            \App\Events\FamilyMemberAdded::dispatch($member);

            $this->auditLogService->log(
                $owner,
                'family.created',
                Family::class,
                (string) $family->family_id,
                null,
                $family->toArray()
            );

            return $family;
        });
    }

    public function updateFamily(Model $actor, Family $family, array $data): Family
    {
        $old = $family->toArray();
        $family->update(array_merge($data, [
            'updated_by' => $actor instanceof User ? $actor->user_id : $actor->staff_id,
        ]));

        $this->auditLogService->log(
            $actor,
            'family.updated',
            Family::class,
            (string) $family->family_id,
            $old,
            $family->toArray()
        );

        return $family;
    }
}
