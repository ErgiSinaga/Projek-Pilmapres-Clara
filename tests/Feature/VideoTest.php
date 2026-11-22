<?php

namespace Tests\Feature;

use App\Models\PetState;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\VideoProgress;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private PetState $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['nama' => 'SD Negeri 1 Magelang', 'kode' => 'S1']);
        $class = SchoolClass::create(['school_id' => $school->id, 'nama' => 'Kelas 4A', 'tingkat' => 4]);

        $this->student = Student::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'kode' => 'S1-01',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => 'Dento Hero',
        ]);

        $this->pet = PetState::create([
            'student_id' => $this->student->id,
            'kebersihan' => 50,
            'kebahagiaan' => 50,
            'koin' => 0,
            'xp' => 0,
            'level' => 1,
            'last_decay_at' => Carbon::now(),
        ]);
    }

    public function test_videos_index_returns_list_with_progressive_unlock(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->getJson(route('api.videos.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'videos' => [
                    '*' => [
                        'id',
                        'urutan',
                        'judul',
                        'durasi_detik',
                        'unlocked',
                        'is_completed',
                    ],
                ],
            ]);

        $videos = $response->json('videos');
        $this->assertCount(4, $videos);
        // Video 1 unlocked, Video 2 locked
        $this->assertTrue($videos[0]['unlocked']);
        $this->assertFalse($videos[1]['unlocked']);
    }

    public function test_can_record_video_progress(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.videos.progress', ['id' => 'video-1']), [
                'detik' => 45,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'detik_ditonton' => 45,
            ]);

        $this->assertDatabaseHas('video_progress', [
            'student_id' => $this->student->id,
            'video_id' => 'video-1',
            'detik_ditonton' => 45,
        ]);
    }

    public function test_complete_requires_90_percent_unless_teacher_assisted(): void
    {
        // Durasi video-1 = 120 detik, 90% = 108 detik.
        // Coba selesaikan saat baru 30 detik
        VideoProgress::create([
            'student_id' => $this->student->id,
            'video_id' => 'video-1',
            'detik_ditonton' => 30,
        ]);

        $failResponse = $this->actingAs($this->student, 'student')
            ->postJson(route('api.videos.complete', ['id' => 'video-1']));

        $failResponse->assertStatus(422);

        // Dengan teacher_assisted = true
        $successResponse = $this->actingAs($this->student, 'student')
            ->postJson(route('api.videos.complete', ['id' => 'video-1']), [
                'teacher_assisted' => true,
            ]);

        $successResponse->assertStatus(200)->assertJson(['completed' => true]);

        $this->assertDatabaseHas('video_progress', [
            'student_id' => $this->student->id,
            'video_id' => 'video-1',
        ]);
    }

    public function test_completing_video_1_unlocks_video_2(): void
    {
        VideoProgress::create([
            'student_id' => $this->student->id,
            'video_id' => 'video-1',
            'detik_ditonton' => 120,
            'selesai_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->student, 'student')
            ->getJson(route('api.videos.index'));

        $response->assertStatus(200);
        $videos = $response->json('videos');

        $this->assertTrue($videos[0]['is_completed']);
        $this->assertTrue($videos[1]['unlocked']);
        $this->assertFalse($videos[2]['unlocked']);
    }

    public function test_video_challenge_awards_rewards_only_once(): void
    {
        VideoProgress::create([
            'student_id' => $this->student->id,
            'video_id' => 'video-1',
            'detik_ditonton' => 120,
            'selesai_at' => Carbon::now(),
        ]);

        // Jawab tantangan: opsi benar = 0 untuk kedua soal
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.videos.challenge', ['id' => 'video-1']), [
                'answers' => [0, 0],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'skor' => 2,
                'reward_awarded' => true,
                'koin_didapat' => 15,
                'xp_didapat' => 20,
                'kebersihan_tambah' => 5,
                'kebahagiaan_tambah' => 5,
            ]);

        $this->pet->refresh();
        $this->assertEquals(15, $this->pet->koin);
        $this->assertEquals(20, $this->pet->xp);
        $this->assertEquals(55, $this->pet->kebersihan);
        $this->assertEquals(55, $this->pet->kebahagiaan);

        // Percobaan kedua: tidak memberikan koin ganda
        $repeatResponse = $this->actingAs($this->student, 'student')
            ->postJson(route('api.videos.challenge', ['id' => 'video-1']), [
                'answers' => [0, 0],
            ]);

        $repeatResponse->assertStatus(200)
            ->assertJson([
                'reward_awarded' => false,
                'koin_didapat' => 0,
            ]);

        $this->pet->refresh();
        $this->assertEquals(15, $this->pet->koin); // tetap 15
    }
}
