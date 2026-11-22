<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private SchoolClass $classA;

    private SchoolClass $classB;

    private User $peneliti;

    private User $guruA;

    private Student $studentA;

    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = School::create(['kode' => 'S1', 'nama' => 'SD Negeri 1 Magelang']);
        $this->schoolB = School::create(['kode' => 'S2', 'nama' => 'SD Negeri 2 Magelang']);

        $this->classA = SchoolClass::create(['school_id' => $this->schoolA->id, 'nama' => 'Kelas 4A', 'tingkat' => 4]);
        $this->classB = SchoolClass::create(['school_id' => $this->schoolB->id, 'nama' => 'Kelas 4B', 'tingkat' => 4]);

        $this->peneliti = User::create([
            'name' => 'Peneliti',
            'email' => 'peneliti@test.com',
            'password' => Hash::make('password'),
            'role' => 'peneliti',
            'school_id' => null,
        ]);

        $this->guruA = User::create([
            'name' => 'Guru A',
            'email' => 'guruA@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'school_id' => $this->schoolA->id,
        ]);

        $this->studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA->id,
            'kode' => 'S1-01',
            'pin_hash' => Hash::make('1111'),
        ]);

        $this->studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'class_id' => $this->classB->id,
            'kode' => 'S2-01',
            'pin_hash' => Hash::make('2222'),
        ]);
    }

    public function test_guru_only_sees_students_from_own_school(): void
    {
        $response = $this->actingAs($this->guruA, 'web')->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('S1-01');
        $response->assertDontSee('S2-01');
    }

    public function test_peneliti_sees_students_from_all_schools(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('S1-01');
        $response->assertSee('S2-01');
    }

    public function test_mass_generate_students_creates_valid_codes_and_hashes_pin(): void
    {
        $response = $this->actingAs($this->guruA, 'web')->post('/admin/students/generate', [
            'class_id' => $this->classA->id,
            'count' => 3,
        ]);

        $response->assertRedirect(route('admin.students.cards'));

        // Codes generated should be S1-02, S1-03, S1-04
        $this->assertDatabaseHas('students', ['kode' => 'S1-02', 'school_id' => $this->schoolA->id]);
        $this->assertDatabaseHas('students', ['kode' => 'S1-03', 'school_id' => $this->schoolA->id]);
        $this->assertDatabaseHas('students', ['kode' => 'S1-04', 'school_id' => $this->schoolA->id]);

        // Verify PINs are hashed, NOT plain
        $newStudent = Student::withoutGlobalScopes()->where('kode', 'S1-02')->first();
        $this->assertNotNull($newStudent->pin_hash);
        $this->assertNotEquals('1234', $newStudent->pin_hash);
        $this->assertTrue(str_starts_with($newStudent->pin_hash, '$2y$') || str_starts_with($newStudent->pin_hash, '$argon2'));

        // Initial pet state is created
        $this->assertDatabaseHas('pet_states', ['student_id' => $newStudent->id]);
    }

    public function test_guru_cannot_generate_students_for_other_school(): void
    {
        $response = $this->actingAs($this->guruA, 'web')->post('/admin/students/generate', [
            'class_id' => $this->classB->id,
            'count' => 2,
        ]);

        $response->assertStatus(403);
    }

    public function test_reset_pin_creates_new_hash_and_unlocks_student(): void
    {
        $this->studentA->update([
            'failed_attempts' => 5,
            'locked_until' => Carbon::now()->addMinutes(10),
        ]);

        $oldHash = $this->studentA->pin_hash;

        $response = $this->actingAs($this->guruA, 'web')
            ->post("/admin/students/{$this->studentA->id}/reset-pin");

        $response->assertSessionHas('new_pin_alert');

        $this->studentA->refresh();
        $this->assertNotEquals($oldHash, $this->studentA->pin_hash);
        $this->assertEquals(0, $this->studentA->failed_attempts);
        $this->assertNull($this->studentA->locked_until);
    }

    public function test_unlock_student_resets_lock_state(): void
    {
        $this->studentA->update([
            'failed_attempts' => 5,
            'locked_until' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->actingAs($this->guruA, 'web')
            ->post("/admin/students/{$this->studentA->id}/unlock");

        $response->assertRedirect();

        $this->studentA->refresh();
        $this->assertEquals(0, $this->studentA->failed_attempts);
        $this->assertNull($this->studentA->locked_until);
    }
}
