<?php

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Device\Device;
use App\Models\Notification\Alert;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => Device::factory(),
            'geofence_id' => null,
            'type' => fake()->randomElement(['geofence_exit', 'sos_panic', 'low_battery', 'fall_detected']),
            'severity' => AlertSeverity::Warning,
            'title' => 'Geofence Exit Alert',
            'body' => 'Device breached designated safety zone.',
            'status' => AlertStatus::Unread,
            'is_read' => false,
            'is_resolved' => false,
            'metadata' => ['battery' => 45],
            'triggered_at' => now(),
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => AlertSeverity::Critical,
            'type' => 'sos_panic',
            'title' => 'CRITICAL SOS PANIC BUTTON ACTIVATED',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AlertStatus::Resolved,
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}
