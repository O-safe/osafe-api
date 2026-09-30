<?php

namespace Database\Seeders;

use App\Models\Admin\Staff;
use App\Models\User\User;
use App\Models\Setup\SetupStatus;
use App\Models\Setup\SetupTitle;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupLga;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $activeStatus = SetupStatus::where('status_name', 'ACTIVE')->value('status_id') ?? SetupStatus::query()->value('status_id');
        $defaultTitle = SetupTitle::query()->value('title_id');
        $defaultGender = SetupGender::query()->value('gender_id');
        $defaultLga = SetupLga::query()->value('lga_id');

        // 1. Seed Development Super Admin Staff
        $adminEmail = env('DEV_ADMIN_EMAIL', 'admin@osafe.test');
        $adminPassword = env('DEV_ADMIN_PASSWORD', 'password123');

        $staff = Staff::firstOrCreate(
            ['email' => $adminEmail],
            [
                'staff_id' => 'STF00120260922000001',
                'title_id' => $defaultTitle,
                'gender_id' => $defaultGender,
                'lga_id' => $defaultLga,
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => $adminEmail,
                'password' => $adminPassword,
                'status_id' => $activeStatus,
                'mobile_number' => '08000000001',
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );

        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($superAdminRole && !$staff->hasRole('Super Admin')) {
            $staff->assignRole($superAdminRole);
        }

        // 2. Seed Development Demo Customer User
        $userEmail = env('DEV_USER_EMAIL', 'user@osafe.test');
        $userPassword = env('DEV_USER_PASSWORD', 'password123');

        User::firstOrCreate(
            ['email' => $userEmail],
            [
                'user_id' => 'USR00120260922000001',
                'title_id' => $defaultTitle,
                'gender_id' => $defaultGender,
                'lga_id' => $defaultLga,
                'first_name' => 'Demo',
                'last_name' => 'User',
                'email' => $userEmail,
                'password' => $userPassword,
                'status_id' => $activeStatus,
                'mobile_number' => '08000000002',
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );
    }
}
