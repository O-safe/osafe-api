<?php

namespace Database\Factories;

use App\Models\Device\Device;
use App\Models\Location\DeviceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceLocationFactory extends Factory
{
    protected $model = DeviceLocation::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'user_id' => null,
            'latitude' => fake()->latitude(6.4, 6.6),
            'longitude' => fake()->longitude(3.2, 3.5),
            'accuracy' => fake()->randomFloat(2, 2.0, 15.0),
            'altitude' => fake()->randomFloat(2, 10.0, 50.0),
            'speed' => fake()->randomFloat(2, 0.0, 45.0),
            'heading' => fake()->randomFloat(2, 0.0, 359.9),
            'source' => 'gps',
            'is_mock' => false,
            'address' => 'Lagos, Nigeria',
            'recorded_at' => now(),
            'received_at' => now(),
        ];
    }
}
