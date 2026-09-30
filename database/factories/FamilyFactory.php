<?php

namespace Database\Factories;

use App\Models\Family\Family;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        return [
            'name' => fake()->lastName() . ' Household',
            'owner_user_id' => User::factory(),
            'max_members' => 6,
        ];
    }
}
