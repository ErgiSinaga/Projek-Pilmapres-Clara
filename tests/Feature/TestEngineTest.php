<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestEngineTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ContentSeeder::class);

        $this->school = School::create(['kode' => 'S1', 'nama' => 'SD Negeri 1 Magelang']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'nama' => 'Kelas 4A', 'tingkat' => 4]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'kode' => 'S1-01',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => 'Dento',
            'failed_attempts' => 0,
            'locked_until' => null,
            'consent_confirmed_at' => now(),
        ]);
    }

    public function test_pre_test_can_be_started_and_does_not_leak_answer_keys(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->getJson('/api/test/pre/start');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'attempt_id',
            'jenis',
            'current_index',
            'total_items',
            'answered_count',
            'questions' => [
                '*' => ['id', 'urutan', 'tp', 'teks', 'opsi', 'opsi_urutan', 'answered'],
            ],
        ]);

        // CRITICAL CHECK: Verify 'benar' is NOT exposed
        $questions = $response->json('questions');
        $this->assertNotEmpty($questions);
        foreach ($questions as $q) {
            $this->assertArrayNotHasKey('benar', $q);
            $this->assertArrayNotHasKey('penjelasan_internal', $q);
        }
    }

    public function test_answering_question_records_answer_without_leaking_correctness_in_response(): void
    {
        $startRes = $this->actingAs($this->student, 'student')->getJson('/api/test/pre/start');
        $firstQuestion = $startRes->json('questions.0');

        $ansRes = $this->actingAs($this->student, 'student')->postJson('/api/test/pre/answer', [
            'test_item_id' => $firstQuestion['id'],
            'pilihan' => 0,
            'ms' => 2400,
            'opsi_urutan' => $firstQuestion['opsi_urutan'],
        ]);

        $ansRes->assertStatus(200);
        $ansRes->assertJsonPath('success', true);
        $ansRes->assertJsonPath('answered_count', 1);

        // Client response must NOT indicate if the answer was right or wrong!
        $this->assertArrayNotHasKey('benar', $ansRes->json());
        $this->assertArrayNotHasKey('is_correct', $ansRes->json());

        // But in database it is recorded
        $this->assertDatabaseHas('test_answers', [
            'test_item_id' => $firstQuestion['id'],
            'ms' => 2400,
        ]);
    }

    public function test_test_can_be_resumed_after_refresh(): void
    {
        $startRes = $this->actingAs($this->student, 'student')->getJson('/api/test/pre/start');
        $firstQuestion = $startRes->json('questions.0');

        // Answer first question
        $this->actingAs($this->student, 'student')->postJson('/api/test/pre/answer', [
            'test_item_id' => $firstQuestion['id'],
            'pilihan' => 1,
            'ms' => 1500,
            'opsi_urutan' => $firstQuestion['opsi_urutan'],
        ]);

        // Simulate page refresh: call start again
        $resumeRes = $this->actingAs($this->student, 'student')->getJson('/api/test/pre/start');
        $resumeRes->assertStatus(200);
        $resumeRes->assertJsonPath('answered_count', 1);
        $resumeRes->assertJsonPath('current_index', 1);
        $this->assertTrue($resumeRes->json('questions.0.answered'));
        $this->assertFalse($resumeRes->json('questions.1.answered'));
    }

    public function test_completed_test_is_locked_and_cannot_be_retaken(): void
    {
        $this->actingAs($this->student, 'student')->getJson('/api/test/pre/start');

        // Finish test
        $finishRes = $this->actingAs($this->student, 'student')->postJson('/api/test/pre/finish');
        $finishRes->assertStatus(200);
        $finishRes->assertJsonPath('stars', 3);

        // Attempting to start again is locked
        $retryRes = $this->actingAs($this->student, 'student')->getJson('/api/test/pre/start');
        $retryRes->assertStatus(403);
    }

    public function test_post_test_is_locked_before_requirements_met(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->getJson('/api/test/post/start');

        $response->assertStatus(403);
        $response->assertJsonPath('unlocked', false);
    }

    public function test_post_test_can_be_unlocked_manually_by_teacher(): void
    {
        $teacher = User::create([
            'name' => 'Guru',
            'email' => 'guru@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'school_id' => $this->school->id,
        ]);

        // Teacher unlocks post test manually
        $this->actingAs($teacher, 'web')
            ->post("/admin/students/{$this->student->id}/unlock-post-test");

        // Now student can start post test
        $response = $this->actingAs($this->student, 'student')
            ->getJson('/api/test/post/start');

        $response->assertStatus(200);
        $response->assertJsonPath('jenis', 'post');
    }

    public function test_student_can_submit_satisfaction_survey(): void
    {
        $getSurvey = $this->actingAs($this->student, 'student')->getJson('/api/survey');
        $getSurvey->assertStatus(200);
        $this->assertCount(6, $getSurvey->json('questions'));

        $postSurvey = $this->actingAs($this->student, 'student')->postJson('/api/survey', [
            'responses' => [
                ['kode' => 'K1', 'nilai' => 5],
                ['kode' => 'K2', 'nilai' => 4],
                ['kode' => 'K3', 'nilai' => 5],
                ['kode' => 'K4', 'nilai' => 5],
                ['kode' => 'K5', 'nilai' => 5],
                ['kode' => 'K6', 'nilai' => 5],
            ],
        ]);

        $postSurvey->assertStatus(200);
        $this->assertDatabaseHas('survey_responses', [
            'student_id' => $this->student->id,
            'butir_kode' => 'K1',
            'nilai' => 5,
        ]);
    }
}
