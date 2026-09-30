<?php

namespace Database\Factories;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password;

    public function definition(): array
    {
        $idNumber = fake()->unique()->numberBetween(100000, 999999);
        return [
            'user_id' => 'USR' . $idNumber . date('Ymd') . fake()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_number' => fake()->phoneNumber(),
            'password' => 'password123',
            'status_id' => \App\Models\Setup\SetupStatus::query()->min('status_id') ?? 1,
            'title_id' => \App\Models\Setup\SetupTitle::query()->min('title_id') ?? 1,
            'gender_id' => \App\Models\Setup\SetupGender::query()->min('gender_id') ?? 1,
            'lga_id' => \App\Models\Setup\SetupLga::query()->min('lga_id') ?? 1,
            'created_by' => 'system',
            'updated_by' => 'system',
        ];
    }
}
