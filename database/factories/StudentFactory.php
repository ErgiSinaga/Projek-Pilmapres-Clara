<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);

        return [
            'school_id' => $school->id,
            'class_id' => $class->id,
            'kode' => $school->kode.'-'.sprintf('%02d', fake()->unique()->numberBetween(1, 99)),
            'pin_hash' => Hash::make('1234'),
            'pet_name' => null,
            'failed_attempts' => 0,
            'locked_until' => null,
            'consent_confirmed_at' => null,
        ];
    }
}
