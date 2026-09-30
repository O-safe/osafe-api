<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class UserSubscriptionPolicy
{
    public function before(Model $actor, string $ability): ?bool
    {
        if ($actor instanceof Staff && $actor->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    public function view(Model $actor, UserSubscription $subscription): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view subscriptions', 'admin');
        }

        if ($actor instanceof User) {
            return $subscription->user_id === $actor->user_id;
        }

        return false;
    }

    public function manage(Model $actor, UserSubscription $subscription): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('manage subscriptions', 'admin');
        }

        if ($actor instanceof User) {
            return $subscription->user_id === $actor->user_id;
        }

        return false;
    }
}
