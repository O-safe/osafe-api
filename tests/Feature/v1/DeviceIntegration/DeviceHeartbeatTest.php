<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_heartbeat_updates_device_online_status_and_battery(): void
    {
        $device = Device::factory()->create([
            'is_online' => false,
            'battery_level' => 50.0,
        ]);
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 92,
            'battery_status' => 'charging',
            'network' => [
                'connection_type' => 'wifi',
                'signal_strength' => -65,
            ],
        ]);

        $response->assertStatus(200);

        $device->refresh();
        $this->assertTrue($device->is_online);
        $this->assertEquals(92.0, $device->battery_level);
        $this->assertEquals('charging', $device->battery_status);
        $this->assertNotNull($device->last_seen_at);
        $this->assertCount(0, $device->locations);
    }

    public function test_heartbeat_validates_battery_range(): void
    {
        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 150,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['battery_level']);
    }
}
