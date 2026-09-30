<?php

namespace Database\Factories;

use App\Models\Geofence\Geofence;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeofenceFactory extends Factory
{
    protected $model = Geofence::class;

    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'family_id' => null,
            'name' => fake()->randomElement(['Home Safety Zone', 'School Area', 'Work Place', 'Park Perimeter']),
            'description' => 'Geofence perimeter for automated location alerts.',
            'center_latitude' => fake()->latitude(6.4, 6.6),
            'center_longitude' => fake()->longitude(3.2, 3.5),
            'radius_meters' => 500,
            'shape' => 'circle',
            'alert_on_entry' => true,
            'alert_on_exit' => true,
            'is_active' => true,
            'color' => '#3B82F6',
            'created_by' => 'system',
            'updated_by' => 'system',
        ];
    }
}
