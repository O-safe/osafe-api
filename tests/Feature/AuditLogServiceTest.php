<?php

namespace Tests\Feature;

use App\Models\Device\Device;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_sensitive_operations_produce_audit_records(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $device = Device::factory()->create();

        $auditService = app(AuditLogService::class);
        $auditService->log(
            $user,
            'device.activated',
            Device::class,
            (string) $device->device_id,
            ['status' => 'unactivated'],
            ['status' => 'active']
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->user_id,
            'actor_type' => 'user',
            'action' => 'device.activated',
            'resource_type' => Device::class,
            'resource_id' => (string) $device->device_id,
        ]);
    }
}
