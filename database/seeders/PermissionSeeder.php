<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // User Management
            'view users',
            'create users',
            'update users',
            'delete users',
            'manage users',

            // Staff & RBAC Infrastructure
            'manage staff',
            'manage roles',
            'view activities',

            // Device Management
            'view devices',
            'create devices',
            'update devices',
            'delete devices',
            'assign devices',
            'unassign devices',
            'manage devices',
            'send device commands',

            // Family Management
            'view families',
            'create families',
            'update families',
            'delete families',
            'manage families',

            // Geofence Management
            'view geofences',
            'create geofences',
            'update geofences',
            'delete geofences',
            'manage geofences',

            // Subscriptions & Billing
            'view subscriptions',
            'manage subscriptions',
            'view billing',
            'manage billing',

            // Alerts & Notifications
            'view alerts',
            'manage alerts',

            // Reports
            'view reports',
            'generate reports',
            'export reports',

            // Customer Support
            'view support tickets',
            'manage support tickets',
            'respond to support tickets',

            // System Administration
            'view audit logs',
            'manage notification templates',
            'manage settings',
            'manage security settings',
            'view system health',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin']
            );
        }
    }
}
