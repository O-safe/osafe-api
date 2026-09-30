<?php

namespace Tests\Feature\v1\Billing;

use App\Exceptions\FamilyLimitExceededException;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLimitTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->subscriptionService = app(SubscriptionService::class);
    }

    public function test_device_limit_is_enforced_by_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['max_devices' => 2]);

        UserSubscription::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $dev1 = Device::factory()->create();
        $dev2 = Device::factory()->create();
        $dev3 = Device::factory()->create();

        DeviceAssignment::create(['device_id' => $dev1->device_id, 'user_id' => $user->user_id, 'assigned_by' => $user->user_id, 'status' => 'active', 'assigned_at' => now()]);
        DeviceAssignment::create(['device_id' => $dev2->device_id, 'user_id' => $user->user_id, 'assigned_by' => $user->user_id, 'status' => 'active', 'assigned_at' => now()]);

        // Third device assignment exceeds plan limit (2)
        $this->expectException(SubscriptionLimitExceededException::class);
        $this->subscriptionService->checkDeviceLimit($user);
    }

    public function test_family_member_limit_is_enforced_by_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['max_family_members' => 2]);

        UserSubscription::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $family = Family::create([
            'name' => 'Test Family',
            'owner_user_id' => $user->user_id,
            'max_members' => 5,
        ]);

        $mem1 = User::factory()->create();
        $mem2 = User::factory()->create();

        FamilyMember::create(['family_id' => $family->family_id, 'user_id' => $mem1->user_id, 'role' => 'adult', 'joined_at' => now()]);
        FamilyMember::create(['family_id' => $family->family_id, 'user_id' => $mem2->user_id, 'role' => 'child', 'joined_at' => now()]);

        // Attempting to check for adding 3rd member when plan max is 2 throws exception
        $this->expectException(FamilyLimitExceededException::class);
        $this->subscriptionService->checkFamilyMemberLimit($user, $family);
    }
}
