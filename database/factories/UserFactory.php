<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'guru',
            'school_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function peneliti(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'peneliti',
            'school_id' => null,
        ]);
    }

    public function guru(?School $school = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guru',
            'school_id' => $school ? $school->id : School::factory(),
        ]);
    }
}
