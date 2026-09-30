<?php

namespace Tests\Feature\v1\DeviceIntegration;

use App\Enums\DeviceCommandStatus;
use App\Enums\DeviceCommandType;
use App\Models\Admin\UserDevice;
use App\Models\Device\Device;
use App\Models\Device\DeviceCommand;
use App\Models\User\User;
use App\Services\Integration\DeviceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceIntegrationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->integrationService = app(DeviceIntegrationService::class);
    }

    public function test_device_a_cannot_acknowledge_command_for_device_b(): void
    {
        $user = User::factory()->create();

        $deviceA = Device::factory()->create();
        $deviceB = Device::factory()->create();

        $credsA = $this->integrationService->issueCredentials($deviceA);
        $this->integrationService->issueCredentials($deviceB);

        $commandB = DeviceCommand::create([
            'device_id' => $deviceB->device_id,
            'issued_by_type' => 'user',
            'issued_by' => $user->user_id,
            'command_type' => DeviceCommandType::LocateNow,
            'status' => DeviceCommandStatus::Sent,
            'sent_at' => now(),
        ]);

        $response = $this->withHeaders(['X-Device-Token' => $credsA['device_token']])
            ->postJson("/api/v1/device-integration/commands/{$commandB->command_id}/ack", [
                'status' => 'executed',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_device_session_token_cannot_authenticate_as_physical_device(): void
    {
        $user = User::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->user_id,
            'device_id' => 'user-login-session-device-123',
            'device_type' => 'iPhone',
            'verified_at' => now(),
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => $userDevice->device_id,
        ])->postJson('/api/v1/device-integration/heartbeat', [
            'battery_level' => 90,
        ]);

        $response->assertStatus(401);
    }
}
