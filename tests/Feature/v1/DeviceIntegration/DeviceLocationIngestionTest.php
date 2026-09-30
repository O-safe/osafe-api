<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Events\DeviceLocationUpdated;
use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeviceLocationIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_device_location_ingestion_creates_location_record(): void
    {
        Event::fake([DeviceLocationUpdated::class]);

        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/location', [
            'latitude' => 6.5244,
            'longitude' => 3.3792,
            'accuracy' => 5.0,
            'altitude' => 12.0,
            'speed' => 1.5,
            'recorded_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.latitude', 6.5244)
            ->assertJsonPath('data.longitude', 3.3792);

        $this->assertDatabaseHas('device_locations', [
            'device_id' => $device->device_id,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ]);
    }

    public function test_device_a_cannot_submit_location_for_device_b(): void
    {
        $deviceA = Device::factory()->create();
        $deviceB = Device::factory()->create();

        $credsA = $this->integrationService->issueCredentials($deviceA);

        $response = $this->withHeaders([
            'X-Device-Token' => $credsA['device_token'],
        ])->postJson('/api/v1/device-integration/location', [
            'device_id' => $deviceB->device_id,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ]);

        $response->assertStatus(403);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/location', [
            'latitude' => 105.0,
            'longitude' => 3.3792,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }
}
