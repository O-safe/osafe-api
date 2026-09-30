<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Super Admin Role
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'admin')->get());

        // 2. Administrator Role
        $admin = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'admin']);
        $adminPermissions = [
            'view users', 'create users', 'update users', 'manage users',
            'manage staff', 'manage roles', 'view activities',
            'view devices', 'create devices', 'update devices', 'assign devices', 'unassign devices', 'manage devices', 'send device commands',
            'view families', 'create families', 'update families', 'manage families',
            'view geofences', 'create geofences', 'update geofences', 'manage geofences',
            'view subscriptions', 'manage subscriptions', 'view billing', 'manage billing',
            'view alerts', 'manage alerts',
            'view reports', 'generate reports', 'export reports',
            'view support tickets', 'manage support tickets', 'respond to support tickets',
            'view audit logs', 'manage notification templates', 'manage settings', 'view system health',
        ];
        $admin->syncPermissions($adminPermissions);

        // 3. Support Officer Role
        $supportOfficer = Role::firstOrCreate(['name' => 'Support Officer', 'guard_name' => 'admin']);
        $supportPermissions = [
            'view users',
            'view devices',
            'view families',
            'view alerts',
            'view support tickets',
            'manage support tickets',
            'respond to support tickets',
        ];
        $supportOfficer->syncPermissions($supportPermissions);

        // 4. Operations Officer Role
        $opsOfficer = Role::firstOrCreate(['name' => 'Operations Officer', 'guard_name' => 'admin']);
        $opsPermissions = [
            'view devices', 'create devices', 'update devices', 'assign devices', 'unassign devices', 'manage devices', 'send device commands',
            'view geofences', 'manage geofences',
            'view alerts', 'manage alerts',
            'view system health',
        ];
        $opsOfficer->syncPermissions($opsPermissions);

        // 5. Auditor Role
        $auditor = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => 'admin']);
        $auditorPermissions = [
            'view users',
            'view devices',
            'view families',
            'view geofences',
            'view subscriptions',
            'view billing',
            'view alerts',
            'view reports',
            'export reports',
            'view audit logs',
            'view activities',
            'view system health',
        ];
        $auditor->syncPermissions($auditorPermissions);
    }
}
