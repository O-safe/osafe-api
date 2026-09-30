<?php

namespace Tests\Feature;

use App\Models\Admin\UserDevice;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCommandApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function authenticateActor(User $user): array
    {
        $deviceId = 'TEST_DEV_' . uniqid();
        UserDevice::create([
            'user_id' => $user->user_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $tokenResult = $user->createToken('auth_token');
        $tokenResult->accessToken->device_id = $deviceId;
        $tokenResult->accessToken->save();

        return [
            'Authorization' => 'Bearer ' . $tokenResult->plainTextToken,
            'X-Device-ID' => $deviceId,
        ];
    }

    public function test_authorized_user_can_issue_normal_device_command()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id, 'is_activated' => true]);
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $headers = $this->authenticateActor($user);

        $response = $this->postJson("/api/v1/user/devices/{$device->device_id}/commands", [
            'command_type' => 'locate_now',
        ], $headers);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.command_type', 'locate_now');
    }

    public function test_sensitive_command_requires_active_subscription()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id, 'is_activated' => true]);
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $headers = $this->authenticateActor($user);

        // Without active subscription -> rejected
        $response = $this->postJson("/api/v1/user/devices/{$device->device_id}/commands", [
            'command_type' => 'take_photo',
        ], $headers);
        $response->assertStatus(403);

        // With active subscription -> allowed
        $plan = SubscriptionPlan::factory()->create();
        UserSubscription::factory()->create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'status' => 'active',
        ]);

        $responseSuccess = $this->postJson("/api/v1/user/devices/{$device->device_id}/commands", [
            'command_type' => 'take_photo',
        ], $headers);
        $responseSuccess->assertStatus(201);
    }

    public function test_unauthorized_user_cannot_issue_command_to_unowned_device()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $device = Device::factory()->create(['registered_by' => $otherUser->user_id, 'is_activated' => true]);

        $headers = $this->authenticateActor($user);

        $response = $this->postJson("/api/v1/user/devices/{$device->device_id}/commands", [
            'command_type' => 'locate_now',
        ], $headers);

        $response->assertStatus(403);
    }
}
