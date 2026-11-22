<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'nama' => 'Kelas '.fake()->randomElement(['3A', '3B', '4A', '4B', '5A', '5B']),
            'tingkat' => fake()->randomElement([3, 4, 5]),
        ];
    }
}
