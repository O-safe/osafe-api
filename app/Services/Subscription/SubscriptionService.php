<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatus;
use App\Exceptions\FamilyLimitExceededException;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function getActiveSubscription(User $user): ?UserSubscription
    {
        return UserSubscription::with('plan')
            ->where('user_id', $user->user_id)
            ->active()
            ->latest('created_at')
            ->first();
    }

    public function checkDeviceLimit(User $user): void
    {
        $subscription = $this->getActiveSubscription($user);
        if (!$subscription || !$subscription->plan) {
            $maxDevices = 1;
        } else {
            $maxDevices = $subscription->plan->max_devices;
        }

        $activeDeviceCount = DeviceAssignment::where('user_id', $user->user_id)
            ->where('status', 'active')
            ->count();

        if ($activeDeviceCount >= $maxDevices) {
            throw SubscriptionLimitExceededException::maxDevicesReached($maxDevices);
        }
    }

    public function checkFamilyMemberLimit(User $user, Family $family): void
    {
        $subscription = $this->getActiveSubscription($user);
        $maxMembers = $subscription && $subscription->plan
            ? min($subscription->plan->max_family_members, $family->max_members)
            : $family->max_members;

        $currentMemberCount = FamilyMember::where('family_id', $family->family_id)->count();

        if ($currentMemberCount >= $maxMembers) {
            throw FamilyLimitExceededException::maxMembersReached($maxMembers);
        }
    }

    public function activateSubscription(User $user, SubscriptionPlan $plan, string $billingCycle = 'monthly', ?BillingTransaction $transaction = null): UserSubscription
    {
        if (!$plan->is_active) {
            throw new InvalidArgumentException("Subscription plan {$plan->name} is not active.");
        }

        return DB::transaction(function () use ($user, $plan, $billingCycle, $transaction) {
            // Cancel existing active/past_due subscriptions for user
            UserSubscription::where('user_id', $user->user_id)
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value, SubscriptionStatus::Pending->value])
                ->update(['status' => SubscriptionStatus::Cancelled->value, 'cancelled_at' => now(), 'auto_renew' => false]);

            $endsAt = strtolower($billingCycle) === 'yearly' ? now()->addYear() : now()->addMonth();

            $subscription = UserSubscription::create([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'auto_renew' => true,
            ]);

            if ($transaction) {
                $transaction->update(['subscription_id' => $subscription->subscription_id]);
            }

            \App\Events\SubscriptionActivated::dispatch($subscription);

            $this->auditLogService->log(
                $user,
                'subscription.activated',
                UserSubscription::class,
                (string) $subscription->subscription_id,
                null,
                [
                    'subscription_id' => $subscription->subscription_id,
                    'status' => $subscription->status->value,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                ],
                ['plan_slug' => $plan->slug, 'billing_cycle' => $billingCycle]
            );

            return $subscription;
        });
    }

    public function renewSubscription(UserSubscription $subscription, ?BillingTransaction $transaction = null): UserSubscription
    {
        return DB::transaction(function () use ($subscription, $transaction) {
            $currentEnd = $subscription->ends_at && $subscription->ends_at->isFuture() ? $subscription->ends_at : now();
            $newEndsAt = $currentEnd->addMonth();

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'ends_at' => $newEndsAt,
                'auto_renew' => true,
            ]);

            if ($transaction) {
                $transaction->update(['subscription_id' => $subscription->subscription_id]);
            }

            $user = User::find($subscription->user_id);
            if ($user) {
                \App\Events\SubscriptionActivated::dispatch($subscription);

                $this->auditLogService->log(
                    $user,
                    'subscription.renewed',
                    UserSubscription::class,
                    (string) $subscription->subscription_id,
                    null,
                    ['ends_at' => $newEndsAt->toIso8601String()]
                );
            }

            return $subscription->fresh();
        });
    }

    public function markPastDue(UserSubscription $subscription): UserSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::PastDue,
        ]);

        $user = User::find($subscription->user_id);
        if ($user) {
            $this->auditLogService->log(
                $user,
                'subscription.past_due',
                UserSubscription::class,
                (string) $subscription->subscription_id
            );
        }

        return $subscription->fresh();
    }

    public function expireSubscription(UserSubscription $subscription): UserSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'auto_renew' => false,
        ]);

        $user = User::find($subscription->user_id);
        if ($user) {
            $this->auditLogService->log(
                $user,
                'subscription.expired',
                UserSubscription::class,
                (string) $subscription->subscription_id
            );
        }

        return $subscription->fresh();
    }

    public function cancelSubscription(User $user, UserSubscription $subscription): UserSubscription
    {
        if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Expired])) {
            throw new InvalidArgumentException("Subscription is already {$subscription->status->value}.");
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        \App\Events\SubscriptionCancelled::dispatch($subscription);

        $this->auditLogService->log(
            $user,
            'subscription.cancelled',
            UserSubscription::class,
            (string) $subscription->subscription_id
        );

        return $subscription->fresh();
    }
}
