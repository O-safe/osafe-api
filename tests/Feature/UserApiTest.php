<?php

namespace Tests\Feature;

use App\Models\Admin\UserDevice;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Geofence\Geofence;
use App\Models\Notification\Alert;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Models\Support\SupportTicket;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
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

    public function test_unauthenticated_user_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/user/dashboard');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();
        $headers = $this->authenticateActor($user);

        $response = $this->getJson('/api/v1/user/dashboard', $headers);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['assigned_devices_count', 'family_members_count', 'unread_alerts_count']]);
    }

    public function test_user_can_only_view_own_assigned_devices()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $device = Device::factory()->create(['registered_by' => $user->user_id]);
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $otherDevice = Device::factory()->create(['registered_by' => $otherUser->user_id]);

        $headers = $this->authenticateActor($user);

        $response = $this->getJson('/api/v1/user/devices', $headers);
        $response->assertStatus(200);

        $deviceIds = collect($response->json('data'))->pluck('device_id');
        $this->assertTrue($deviceIds->contains($device->device_id));
        $this->assertFalse($deviceIds->contains($otherDevice->device_id));
    }

    public function test_cross_user_device_access_is_rejected()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherDevice = Device::factory()->create(['registered_by' => $otherUser->user_id]);

        $headers = $this->authenticateActor($user);

        $response = $this->getJson("/api/v1/user/devices/{$otherDevice->device_id}", $headers);
        $response->assertStatus(403);
    }

    public function test_user_can_create_and_manage_family()
    {
        $user = User::factory()->create();
        $headers = $this->authenticateActor($user);

        $storeResponse = $this->postJson('/api/v1/user/families', [
            'name' => 'The Smith Family',
            'max_members' => 5,
        ], $headers);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $familyId = $storeResponse->json('data.family_id');

        $showResponse = $this->getJson("/api/v1/user/families/{$familyId}", $headers);
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'The Smith Family');
    }

    public function test_cross_user_support_ticket_access_is_rejected()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-1001',
            'user_id' => $otherUser->user_id,
            'subject' => 'Issue with device',
            'description' => 'Help me please',
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $headers = $this->authenticateActor($user);

        $response = $this->getJson("/api/v1/user/support/tickets/{$ticket->ticket_id}", $headers);
        $response->assertStatus(403);
    }
}
