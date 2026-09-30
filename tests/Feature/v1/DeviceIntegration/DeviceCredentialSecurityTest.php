<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Models\Admin\Staff;
use App\Models\Admin\UserDevice;
use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_admin_can_issue_rotate_and_revoke_device_credentials(): void
    {
        $admin = Staff::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->first();

        $deviceId = 'TEST_ADMIN_DEV_' . uniqid();
        UserDevice::create([
            'user_id' => $admin->staff_id,
            'device_id' => $deviceId,
            'device_type' => 'TestRunner',
            'verified_at' => now(),
        ]);

        $device = Device::factory()->create();

        // 1. Issue credentials
        $issueResponse = $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->postJson("/api/v1/admin/devices/{$device->device_id}/credentials/issue", [
                'platform' => 'osafe_tracker',
            ]);

        $issueResponse->assertStatus(201)
            ->assertJsonStructure(['data' => ['device_id', 'integration_id', 'platform', 'device_token']]);

        $token1 = $issueResponse->json('data.device_token');

        // Test token1 works
        $hb1 = $this->withHeaders(['X-Device-Token' => $token1])
            ->postJson('/api/v1/device-integration/heartbeat', ['battery_level' => 80]);
        $hb1->assertStatus(200);

        // 2. Rotate credentials
        $rotateResponse = $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->postJson("/api/v1/admin/devices/{$device->device_id}/credentials/rotate", [
                'platform' => 'osafe_tracker',
            ]);

        $rotateResponse->assertStatus(200);
        $token2 = $rotateResponse->json('data.device_token');
        $this->assertNotEquals($token1, $token2);

        // Old token1 fails
        $this->withHeaders(['X-Device-Token' => $token1])
            ->postJson('/api/v1/device-integration/heartbeat', ['battery_level' => 80])
            ->assertStatus(401);

        // New token2 works
        $this->withHeaders(['X-Device-Token' => $token2])
            ->postJson('/api/v1/device-integration/heartbeat', ['battery_level' => 80])
            ->assertStatus(200);

        // 3. Revoke credentials
        $revokeResponse = $this->actingAs($admin, 'admin')
            ->withHeaders(['X-Device-ID' => $deviceId])
            ->postJson("/api/v1/admin/devices/{$device->device_id}/credentials/revoke", [
                'platform' => 'osafe_tracker',
            ]);

        $revokeResponse->assertStatus(200)
            ->assertJsonPath('revoked', true);

        // Token2 now fails
        $this->withHeaders(['X-Device-Token' => $token2])
            ->postJson('/api/v1/device-integration/heartbeat', ['battery_level' => 80])
            ->assertStatus(401);
    }
}
