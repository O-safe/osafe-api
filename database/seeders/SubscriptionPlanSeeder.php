<?php

namespace Database\Seeders;

use App\Models\Subscription\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Standard Plan',
                'slug' => 'standard',
                'description' => 'Single device safety and tracking plan for individual monitoring.',
                'max_devices' => 1,
                'max_family_members' => 1,
                'location_history_days' => 30,
                'price_monthly' => 9.99,
                'price_yearly' => 99.99,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    'real_time_gps' => true,
                    'geofencing' => true,
                    'sos_alerts' => true,
                    'history_days' => 30,
                    'device_remote_control' => true,
                ],
            ],
            [
                'name' => 'Family Plan',
                'slug' => 'family',
                'description' => 'Multi-device family safety plan supporting up to 5 devices and 6 family members.',
                'max_devices' => 5,
                'max_family_members' => 6,
                'location_history_days' => 90,
                'price_monthly' => 19.99,
                'price_yearly' => 199.99,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => [
                    'real_time_gps' => true,
                    'geofencing' => true,
                    'sos_alerts' => true,
                    'family_sharing' => true,
                    'history_days' => 90,
                    'device_remote_control' => true,
                    'priority_support' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
