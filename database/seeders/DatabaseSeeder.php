<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            MeansOfIdentificationSeeder::class,
            SetupCounterSeeder::class,
            SetupGenderSeeder::class,
            SetupTitleSeeder::class,
            SetupStatusSeeder::class,
            SetupCountrySeeder::class,
            SetupStateSeeder::class,
            SetupLgaSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SubscriptionPlanSeeder::class,
            StaffUserSeeder::class,
        ]);

        if (app()->environment('local', 'testing', 'development')) {
            $this->call([
                OsafeDevelopmentSeeder::class,
            ]);
        }
    }
}
