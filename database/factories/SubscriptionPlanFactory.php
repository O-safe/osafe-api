<?php

namespace Database\Factories;

use App\Models\Subscription\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $code = fake()->unique()->word();
        return [
            'name' => ucfirst($code) . ' Plan',
            'slug' => strtolower($code),
            'description' => fake()->sentence(),
            'max_devices' => 2,
            'max_family_members' => 4,
            'location_history_days' => 30,
            'price_monthly' => fake()->randomFloat(2, 5, 49),
            'price_yearly' => fake()->randomFloat(2, 50, 499),
            'currency' => 'USD',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'features' => ['gps' => true, 'geofence' => true],
        ];
    }
}
