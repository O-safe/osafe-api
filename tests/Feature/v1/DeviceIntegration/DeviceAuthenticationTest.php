<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_valid_device_token_authenticates_device(): void
    {
        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 85,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Device heartbeat recorded successfully.');
    }

    public function test_missing_device_token_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 85,
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Missing physical device authentication credentials.');
    }

    public function test_invalid_device_token_returns_unauthorized(): void
    {
        $response = $this->withHeaders([
            'X-Device-Token' => 'invalid_token_secret_123',
        ])->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 85,
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid or revoked device integration credentials.');
    }

    public function test_revoked_credentials_cannot_authenticate(): void
    {
        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $this->integrationService->revokeCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 85,
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid or revoked device integration credentials.');
    }
}
