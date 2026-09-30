<?php

namespace Tests\Feature;

use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeviceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_assigned_device(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create();
        $device = Device::factory()->create();

        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $device));
        $this->assertTrue(Gate::forUser($user)->allows('update', $device));
    }

    public function test_unauthorized_user_cannot_access_another_users_device(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $device = Device::factory()->create();

        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $userA->user_id,
            'status' => 'active',
        ]);

        $this->assertFalse(Gate::forUser($userB)->allows('view', $device));
        $this->assertFalse(Gate::forUser($userB)->allows('update', $device));
    }

    public function test_family_member_can_access_family_assigned_device(): void
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

        $device = Device::factory()->create();
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'family_id' => $family->family_id,
            'user_id' => $owner->user_id,
            'status' => 'active',
        ]);

        $this->assertTrue(Gate::forUser($memberUser)->allows('view', $device));
    }

    public function test_admin_staff_permission_works_correctly(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $device = Device::factory()->create();

        $this->assertTrue(Gate::forUser($staff)->allows('view', $device));
        $this->assertTrue(Gate::forUser($staff)->allows('update', $device));
    }
}
