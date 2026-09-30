<?php

namespace Tests\Feature;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_is_recognized(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::where('slug', 'standard')->first();

        $service = app(SubscriptionService::class);
        $subscription = $service->activateSubscription($user, $plan);

        $this->assertNotNull($subscription);
        $this->assertEquals($plan->plan_id, $subscription->plan_id);
        $this->assertTrue($subscription->status->value === 'active');
    }

    public function test_device_limits_are_enforced_by_subscription_plan(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $plan = SubscriptionPlan::where('slug', 'standard')->first(); // max_devices = 2

        $service = app(SubscriptionService::class);
        $service->activateSubscription($user, $plan);

        // Assign 2 active devices
        for ($i = 0; $i < 2; $i++) {
            $device = Device::factory()->create();
            DeviceAssignment::factory()->create([
                'device_id' => $device->device_id,
                'user_id' => $user->user_id,
                'status' => 'active',
            ]);
        }

        $this->expectException(SubscriptionLimitExceededException::class);
        $service->checkDeviceLimit($user);
    }
}
