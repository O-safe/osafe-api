<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\Device\Device;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeviceStatusIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_device_status_update_persists_history_and_dispatches_event(): void
    {
        Event::fake([DeviceStatusChanged::class]);

        $device = Device::factory()->create([
            'status' => DeviceStatus::Unactivated,
        ]);
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/status', [
            'status' => 'active',
            'reason' => 'Device completed self-activation sequence.',
        ]);

        $response->assertStatus(200);

        $device->refresh();
        $this->assertEquals(DeviceStatus::Active, $device->status);

        $this->assertDatabaseHas('device_status_history', [
            'device_id' => $device->device_id,
            'previous_status' => 'unactivated',
            'new_status' => 'active',
            'changed_by_type' => 'device',
        ]);

        Event::assertDispatched(DeviceStatusChanged::class);
    }

    public function test_invalid_device_status_value_is_rejected(): void
    {
        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $response = $this->withHeaders([
            'X-Device-Token' => $creds['device_token'],
        ])->postJson('/api/v1/device-integration/status', [
            'status' => 'super_active_invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
