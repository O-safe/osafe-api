<?php

namespace Tests\Feature\v1\Realtime;

use App\Models\Admin\Staff;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function evaluateChannel(string $channelPattern, $user, ...$args): bool
    {
        $channels = Broadcast::getChannels();
        $this->assertArrayHasKey($channelPattern, $channels, "Channel pattern {$channelPattern} not registered in routes/channels.php");
        
        $callback = $channels[$channelPattern];
        return (bool) call_user_func($callback, $user, ...$args);
    }

    public function test_unauthenticated_user_rejected_from_private_channels()
    {
        $allowed = $this->evaluateChannel('user.{userId}', null, '123');
        $this->assertFalse($allowed);
    }

    public function test_user_can_subscribe_to_own_user_channel()
    {
        $user = User::factory()->create();
        $allowed = $this->evaluateChannel('user.{userId}', $user, $user->user_id);
        $this->assertTrue($allowed);
    }

    public function test_user_cannot_subscribe_to_another_users_channel()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $allowed = $this->evaluateChannel('user.{userId}', $userA, $userB->user_id);
        $this->assertFalse($allowed);
    }

    public function test_family_member_can_subscribe_to_family_channel()
    {
        $owner = User::factory()->create();
        $memberUser = User::factory()->create();

        $family = Family::factory()->create(['owner_user_id' => $owner->user_id]);
        FamilyMember::factory()->create([
            'family_id' => $family->family_id,
            'user_id' => $memberUser->user_id,
            'role' => 'member',
            'status_id' => 1,
        ]);

        $allowed = $this->evaluateChannel('family.{familyId}', $memberUser, (string) $family->family_id);
        $this->assertTrue($allowed);
    }

    public function test_non_family_member_cannot_subscribe_to_family_channel()
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $family = Family::factory()->create(['owner_user_id' => $owner->user_id]);

        $allowed = $this->evaluateChannel('family.{familyId}', $outsider, (string) $family->family_id);
        $this->assertFalse($allowed);
    }

    public function test_device_owner_can_subscribe_to_device_channel()
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['registered_by' => $user->user_id]);
        DeviceAssignment::factory()->create([
            'device_id' => $device->device_id,
            'user_id' => $user->user_id,
            'status' => 'active',
        ]);

        $allowed = $this->evaluateChannel('device.{deviceId}', $user, (string) $device->device_id);
        $this->assertTrue($allowed);
    }

    public function test_unauthorized_user_cannot_subscribe_to_device_channel()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $device = Device::factory()->create(['registered_by' => $userA->user_id]);

        $allowed = $this->evaluateChannel('device.{deviceId}', $userB, (string) $device->device_id);
        $this->assertFalse($allowed);
    }

    public function test_ordinary_customer_cannot_subscribe_to_admin_channels()
    {
        $user = User::factory()->create();

        $allowedDashboard = $this->evaluateChannel('admin.dashboard', $user);
        $this->assertFalse($allowedDashboard);

        $allowedAudit = $this->evaluateChannel('admin.audit', $user);
        $this->assertFalse($allowedAudit);
    }

    public function test_authorized_admin_can_subscribe_to_admin_channels()
    {
        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $this->assertNotNull($staff);

        $perm = Permission::findOrCreate('view audit logs', 'admin');
        $staff->givePermissionTo($perm);

        $allowedAudit = $this->evaluateChannel('admin.audit', $staff);
        $this->assertTrue($allowedAudit);
    }

    public function test_super_admin_can_access_all_channels()
    {
        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $staff->syncRoles(['Super Admin']);

        $this->assertTrue($this->evaluateChannel('admin.dashboard', $staff));
        $this->assertTrue($this->evaluateChannel('admin.audit', $staff));
        $this->assertTrue($this->evaluateChannel('user.{userId}', $staff, 'USR123'));
        $this->assertTrue($this->evaluateChannel('family.{familyId}', $staff, '1'));
        $this->assertTrue($this->evaluateChannel('device.{deviceId}', $staff, '1'));
    }

    public function test_auditor_role_access_matrix()
    {
        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $staff->syncRoles(['Auditor']);

        $this->assertTrue($this->evaluateChannel('admin.audit', $staff));
        $this->assertTrue($this->evaluateChannel('user.{userId}', $staff, 'USR123'));
        $this->assertTrue($this->evaluateChannel('family.{familyId}', $staff, '1'));
        $this->assertTrue($this->evaluateChannel('device.{deviceId}', $staff, '1'));
    }

    public function test_support_officer_cannot_access_audit_channel()
    {
        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $staff->syncRoles(['Support Officer']);

        $this->assertFalse($this->evaluateChannel('admin.audit', $staff));
        $this->assertTrue($this->evaluateChannel('admin.dashboard', $staff));
        $this->assertTrue($this->evaluateChannel('user.{userId}', $staff, 'USR123'));
    }

    public function test_staff_without_view_users_cannot_access_user_channel()
    {
        $staff = Staff::where('email', 'admin@osafe.test')->first();
        $staff->syncRoles([]);
        $staff->syncPermissions([]);

        $this->assertFalse($this->evaluateChannel('user.{userId}', $staff, 'USR123'));
        $this->assertFalse($this->evaluateChannel('admin.dashboard', $staff));
        $this->assertFalse($this->evaluateChannel('admin.audit', $staff));
    }
}
