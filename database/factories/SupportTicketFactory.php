<?php

namespace Database\Factories;

use App\Enums\SupportTicketStatus;
use App\Models\Support\SupportTicket;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-' . date('Ymd') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'user_id' => User::factory(),
            'device_id' => null,
            'assigned_to' => null,
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => 'device',
            'priority' => 'medium',
            'status' => SupportTicketStatus::Open,
            'closed_at' => null,
        ];
    }
}
