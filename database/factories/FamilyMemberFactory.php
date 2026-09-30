<?php

namespace Database\Factories;

use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyMemberFactory extends Factory
{
    protected $model = FamilyMember::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['owner', 'admin', 'member', 'guardian', 'child']),
            'relationship' => fake()->randomElement(['parent', 'child', 'spouse']),
            'joined_at' => now(),
            'status_id' => 1,
        ];
    }
}
