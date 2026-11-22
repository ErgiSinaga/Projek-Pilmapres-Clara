<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAuthTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SchoolClass $class;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'kode' => 'S1',
            'nama' => 'SD Negeri 1 Magelang',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'nama' => 'Kelas 4A',
            'tingkat' => 4,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'kode' => 'S1-01',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => null,
            'failed_attempts' => 0,
            'locked_until' => null,
            'consent_confirmed_at' => null,
        ]);
    }

    public function test_student_can_login_with_valid_code_and_pin(): void
    {
        $response = $this->postJson('/api/login', [
            'kode' => 'S1-01',
            'pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('student.kode', 'S1-01');
        $this->assertTrue(Auth::guard('student')->check());
        $this->assertEquals($this->student->id, Auth::guard('student')->id());

        // Login log recorded
        $this->assertDatabaseHas('login_logs', [
            'student_id' => $this->student->id,
        ]);

        // Pet state initialized
        $this->assertDatabaseHas('pet_states', [
            'student_id' => $this->student->id,
        ]);
    }

    public function test_student_login_with_wrong_pin_fails_and_increments_failed_attempts(): void
    {
        $response = $this->postJson('/api/login', [
            'kode' => 'S1-01',
            'pin' => '9999',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
        $this->assertFalse(Auth::guard('student')->check());

        $this->student->refresh();
        $this->assertEquals(1, $this->student->failed_attempts);
    }

    public function test_student_is_locked_after_max_failed_attempts(): void
    {
        $this->student->update(['failed_attempts' => 4]);

        $response = $this->postJson('/api/login', [
            'kode' => 'S1-01',
            'pin' => '9999',
        ]);

        $response->assertStatus(423);
        $response->assertSee('Minta bantuan guru ya');

        $this->student->refresh();
        $this->assertTrue($this->student->isLocked());
    }

    public function test_locked_student_cannot_login_even_with_correct_pin(): void
    {
        $this->student->update([
            'locked_until' => Carbon::now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/login', [
            'kode' => 'S1-01',
            'pin' => '1234',
        ]);

        $response->assertStatus(423);
        $this->assertFalse(Auth::guard('student')->check());
    }

    public function test_student_can_check_me_profile_and_logout(): void
    {
        $this->postJson('/api/login', [
            'kode' => 'S1-01',
            'pin' => '1234',
        ]);

        $meResponse = $this->getJson('/api/me');
        $meResponse->assertStatus(200);
        $meResponse->assertJsonPath('student.kode', 'S1-01');

        $logoutResponse = $this->postJson('/api/logout');
        $logoutResponse->assertStatus(200);
        $this->assertFalse(Auth::guard('student')->check());
    }
}
