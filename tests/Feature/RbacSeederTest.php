<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_and_roles_are_seeded_correctly(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        // 1. Verify permissions count
        $this->assertDatabaseHas('permissions', ['name' => 'view devices', 'guard_name' => 'admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'send device commands', 'guard_name' => 'admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage geofences', 'guard_name' => 'admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage security settings', 'guard_name' => 'admin']);

        // 2. Verify roles creation
        $roles = ['Super Admin', 'Administrator', 'Support Officer', 'Operations Officer', 'Auditor'];
        foreach ($roles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName, 'guard_name' => 'admin']);
        }

        // 3. Verify Super Admin has all permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $allPermissionsCount = Permission::where('guard_name', 'admin')->count();
        $this->assertEquals($allPermissionsCount, $superAdmin->permissions()->count());

        // 4. Verify Support Officer role least privilege scoping
        $supportOfficer = Role::where('name', 'Support Officer')->first();
        $this->assertTrue($supportOfficer->hasPermissionTo('view support tickets'));
        $this->assertFalse($supportOfficer->hasPermissionTo('manage security settings'));
    }
}
