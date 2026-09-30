<?php

namespace Tests\Feature\v1\Billing;

use App\Models\Admin\Staff;
use App\Models\Admin\UserDevice;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_user_cannot_view_or_cancel_another_users_subscription(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $dev1 = 'DEV_AUTH_1_' . uniqid();
        UserDevice::create(['user_id' => $user1->user_id, 'device_id' => $dev1, 'device_type' => 'Test', 'verified_at' => now()]);

        $plan = SubscriptionPlan::factory()->create();
        $sub2 = UserSubscription::create([
            'user_id' => $user2->user_id,
            'plan_id' => $plan->plan_id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // User1 tries to view User2's subscription -> 403
        $this->actingAs($user1, 'user')
            ->withHeaders(['X-Device-ID' => $dev1])
            ->getJson("/api/v1/user/subscriptions/{$sub2->subscription_id}")
            ->assertStatus(403);

        // User1 tries to cancel User2's subscription -> 403
        $this->actingAs($user1, 'user')
            ->withHeaders(['X-Device-ID' => $dev1])
            ->postJson("/api/v1/user/subscriptions/{$sub2->subscription_id}/cancel")
            ->assertStatus(403);
    }

    public function test_ordinary_customer_cannot_manage_plans(): void
    {
        $user = User::factory()->create();
        $dev = 'DEV_CUST_PLAN_' . uniqid();
        UserDevice::create(['user_id' => $user->user_id, 'device_id' => $dev, 'device_type' => 'Test', 'verified_at' => now()]);

        $this->actingAs($user, 'user')
            ->withHeaders(['X-Device-ID' => $dev])
            ->postJson('/api/v1/admin/plans', [
                'name' => 'Fake Plan',
                'slug' => 'fake-plan',
                'price_monthly' => 10,
                'price_yearly' => 100,
            ])
            ->assertStatus(401);
    }

    public function test_super_admin_can_view_and_cancel_any_subscription(): void
    {
        $admin = Staff::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->first();
        $devAdmin = 'DEV_ADMIN_SUB_' . uniqid();
        UserDevice::create(['user_id' => $admin->staff_id, 'device_id' => $devAdmin, 'device_type' => 'Test', 'verified_at' => now()]);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $sub = UserSubscription::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $devAdmin])
            ->getJson("/api/v1/admin/subscriptions/{$sub->subscription_id}")
            ->assertStatus(200);

        $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $devAdmin])
            ->postJson("/api/v1/admin/subscriptions/{$sub->subscription_id}/cancel")
            ->assertStatus(200);
    }
}
