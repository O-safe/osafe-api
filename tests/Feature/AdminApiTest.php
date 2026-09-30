<?php

namespace Tests\Feature;

use App\Models\Admin\Staff;
use App\Models\Admin\UserDevice;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function authenticateStaff(Staff $staff): array
    {
        $deviceId = 'TEST_DEV_' . uniqid();
        UserDevice::create([
            'user_id' => $staff->staff_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $tokenResult = $staff->createToken('auth_token');
        $tokenResult->accessToken->device_id = $deviceId;
        $tokenResult->accessToken->save();

        return [
            'Authorization' => 'Bearer ' . $tokenResult->plainTextToken,
            'X-Device-ID' => $deviceId,
        ];
    }

    protected function authenticateUser(User $user): array
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

    public function test_unauthenticated_admin_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/admin/dashboard');
        $response->assertStatus(401);
    }

    public function test_public_staff_crud_is_impossible()
    {
        $response = $this->getJson('/api/v1/admin/staff');
        $response->assertStatus(401);

        $postResponse = $this->postJson('/api/v1/admin/staff', [
            'firstName' => 'Hacker',
            'lastName' => 'User',
        ]);
        $postResponse->assertStatus(401);
    }

    public function test_super_admin_can_access_admin_dashboard()
    {
        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $this->assertNotNull($staff);

        $headers = $this->authenticateStaff($staff);

        $response = $this->getJson('/api/v1/admin/dashboard', $headers);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['users', 'devices', 'families', 'subscriptions']]);
    }

    public function test_customer_user_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create();
        $headers = $this->authenticateUser($user);

        $response = $this->getJson('/api/v1/admin/dashboard', $headers);
        $response->assertStatus(401);
    }

    public function test_audit_logs_access_restricted_to_admin()
    {
        $user = User::factory()->create();
        $userHeaders = $this->authenticateUser($user);

        $userResponse = $this->getJson('/api/v1/admin/audit-logs', $userHeaders);
        $userResponse->assertStatus(401);

        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $adminHeaders = $this->authenticateStaff($staff);

        $adminResponse = $this->getJson('/api/v1/admin/audit-logs', $adminHeaders);
        $adminResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
