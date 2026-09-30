<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Models\Device\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'serial_number' => 'SN-' . strtoupper(fake()->bothify('??###???')),
            'imei' => fake()->numerify('###############'),
            'model' => 'O SAFE Band Pro ' . fake()->randomElement(['v1', 'v2', 'v3']),
            'firmware_version' => '2.4.12',
            'status' => DeviceStatus::Active,
            'battery_level' => fake()->numberBetween(20, 100),
            'battery_status' => 'discharging',
            'last_seen_at' => now(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Inactive,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceStatus::Suspended,
        ]);
    }
}
