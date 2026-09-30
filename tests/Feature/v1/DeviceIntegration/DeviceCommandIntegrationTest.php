<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceCommandType;
use App\Events\DeviceCommandStatusChanged;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use App\Models\User\User;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeviceCommandIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_device_can_fetch_and_acknowledge_pending_commands(): void
    {
        Event::fake([DeviceCommandStatusChanged::class]);

        $user = User::factory()->create();
        $device = Device::factory()->create();
        $creds = $this->integrationService->issueCredentials($device);

        $command = DeviceCommand::create([
            'device_id' => $device->device_id,
            'issued_by_type' => 'user',
            'issued_by' => $user->user_id,
            'command_type' => DeviceCommandType::LocateNow,
            'status' => DeviceCommandStatus::Sent,
            'sent_at' => now(),
        ]);

        // 1. Fetch pending commands
        $fetchResponse = $this->withHeaders(['X-Device-Token' => $creds['device_token']])
            ->getJson('/api/v1/device-integration/commands');

        $fetchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.command_id', $command->command_id);

        // 2. Acknowledge command execution
        $ackResponse = $this->withHeaders(['X-Device-Token' => $creds['device_token']])
            ->postJson("/api/v1/device-integration/commands/{$command->command_id}/ack", [
                'status' => 'executed',
                'response_payload' => ['latency_ms' => 45],
            ]);

        $ackResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'executed');

        $command->refresh();
        $this->assertEquals(DeviceCommandStatus::Executed, $command->status);
        $this->assertNotNull($command->executed_at);

        $this->assertDatabaseHas('device_command_logs', [
            'command_id' => $command->command_id,
            'device_id' => $device->device_id,
            'event' => 'executed',
        ]);

        Event::assertDispatched(DeviceCommandStatusChanged::class);
    }
}
