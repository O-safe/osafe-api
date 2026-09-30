<?php

namespace Tests\Feature\v1\Billing;

use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCancelled;
use App\Models\Admin\UserDevice;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->subscriptionService = app(SubscriptionService::class);
    }

    public function test_user_can_initialize_checkout_and_activate_subscription(): void
    {
        Event::fake([SubscriptionActivated::class]);

        $user = User::factory()->create();
        $deviceId = 'DEV_SUB_LIFECYCLE_' . uniqid();
        UserDevice::create([
            'user_id' => $user->user_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $plan = SubscriptionPlan::where('slug', 'pro')->first() ?? SubscriptionPlan::factory()->create();

        // 1. Checkout
        $checkoutResponse = $this->actingAs($user, 'user')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->postJson('/api/v1/user/subscriptions/checkout', [
                'plan_id' => $plan->plan_id,
                'billing_cycle' => 'monthly',
            ]);

        $checkoutResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['transaction' => ['reference', 'amount', 'status'], 'checkout_url', 'reference'],
            ]);

        $reference = $checkoutResponse->json('data.reference');

        // 2. Verification / Activation
        $verifyResponse = $this->actingAs($user, 'user')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->postJson("/api/v1/user/billing/verify/{$reference}");

        $verifyResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'successful');

        $activeSub = $this->subscriptionService->getActiveSubscription($user);
        $this->assertNotNull($activeSub);
        $this->assertEquals($plan->plan_id, $activeSub->plan_id);
        $this->assertEquals(SubscriptionStatus::Active, $activeSub->status);

        Event::assertDispatched(SubscriptionActivated::class);
    }

    public function test_renewal_extends_subscription_period(): void
    {
        Event::fake([SubscriptionActivated::class]);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $sub = UserSubscription::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(2),
            'auto_renew' => true,
        ]);

        $oldEndsAt = $sub->ends_at->copy();
        $renewed = $this->subscriptionService->renewSubscription($sub);

        $this->assertEquals(SubscriptionStatus::Active, $renewed->status);
        $this->assertTrue($renewed->ends_at->isAfter($oldEndsAt));

        Event::assertDispatched(SubscriptionActivated::class);
    }

    public function test_subscription_expiration(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $sub = UserSubscription::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
            'auto_renew' => true,
        ]);

        $expired = $this->subscriptionService->expireSubscription($sub);

        $this->assertEquals(SubscriptionStatus::Expired, $expired->status);
        $this->assertFalse($expired->auto_renew);
    }

    public function test_user_can_cancel_subscription(): void
    {
        Event::fake([SubscriptionCancelled::class]);

        $user = User::factory()->create();
        $deviceId = 'DEV_CANCEL_' . uniqid();
        UserDevice::create([
            'user_id' => $user->user_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $plan = SubscriptionPlan::factory()->create();
        $sub = UserSubscription::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user, 'user')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->postJson("/api/v1/user/subscriptions/{$sub->subscription_id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals(SubscriptionStatus::Cancelled, $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->cancelled_at);

        Event::assertDispatched(SubscriptionCancelled::class);
    }
}
