<?php

namespace Database\Factories;

use App\Enums\DeviceAssignmentStatus;
use App\Models\Device\Device;
use App\Models\Device\DeviceAssignment;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceAssignmentFactory extends Factory
{
    protected $model = DeviceAssignment::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_by_type' => 'user',
            'status' => DeviceAssignmentStatus::Active,
            'assigned_at' => now()->subDays(5),
            'revoked_at' => null,
            'metadata' => ['notes' => 'Primary device assignment for user safety monitoring.'],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceAssignmentStatus::Active,
            'revoked_at' => null,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeviceAssignmentStatus::Revoked,
            'revoked_at' => now(),
            'revocation_reason' => 'User unassigned device.',
        ]);
    }
}
