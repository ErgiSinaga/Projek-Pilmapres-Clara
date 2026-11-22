<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $code = 'S'.fake()->unique()->numberBetween(1, 999);

        return [
            'kode' => $code,
            'nama' => 'SD Negeri '.fake()->city(),
        ];
    }
}
