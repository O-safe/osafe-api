<?php

namespace Tests\Feature;

use App\Enums\DeviceCommandType;
use App\Exceptions\DeviceAccessDeniedException;
use App\Exceptions\UnauthorizedCommandException;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\User\User;
use App\Services\Device\DeviceCommandAuthorizationService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCommandAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_permitted_normal_command_passes_authorization(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $device = Device::factory()->active()->create();

        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $service = app(DeviceCommandAuthorizationService::class);
        $command = $service->authorizeAndQueueCommand($user, $device, DeviceCommandType::LocateNow);

        $this->assertNotNull($command);
        $this->assertEquals($device->device_id, $command->device_id);
    }

    public function test_unauthorized_user_command_is_rejected(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $device = Device::factory()->active()->create(); // unassigned to user

        $service = app(DeviceCommandAuthorizationService::class);

        $this->expectException(UnauthorizedCommandException::class);
        $service->authorizeAndQueueCommand($user, $device, DeviceCommandType::LocateNow);
    }

    public function test_inactive_device_command_is_rejected(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $device = Device::factory()->inactive()->create();

        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $service = app(DeviceCommandAuthorizationService::class);

        $this->expectException(DeviceAccessDeniedException::class);
        $service->authorizeAndQueueCommand($user, $device, DeviceCommandType::LocateNow);
    }

    public function test_destructive_command_requires_family_owner_or_admin_status(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $owner = User::factory()->create();
        $memberUser = User::factory()->create();

        $family = Family::factory()->create(['owner_user_id' => $owner->user_id]);
        FamilyMember::factory()->create([
            'family_id' => $family->family_id,
            'user_id' => $memberUser->user_id,
            'role' => 'member',
        ]);

        $device = Device::factory()->active()->create();
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'family_id' => $family->family_id,
            'user_id' => $owner->user_id,
            'status' => 'active',
        ]);

        // Activate subscription for member user
        $plan = SubscriptionPlan::where('slug', 'family')->first();
        app(SubscriptionService::class)->activateSubscription($memberUser, $plan);

        $service = app(DeviceCommandAuthorizationService::class);

        // Ordinary member should fail for wipe_device
        $this->expectException(UnauthorizedCommandException::class);
        $service->authorizeAndQueueCommand($memberUser, $device, DeviceCommandType::WipeDevice);
    }
}
