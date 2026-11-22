<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\PetState;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiniGameTest extends TestCase
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

    public function test_can_start_pilah_makanan_and_returns_items(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.games.start', ['kode' => 'pilah-makanan']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'game_kode',
                'items',
            ]);

        $this->assertDatabaseHas('game_sessions', [
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
        ]);
    }

    public function test_can_start_sikat_bersih_and_returns_zones(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.games.start', ['kode' => 'sikat-bersih']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'game_kode',
                'zones',
                'timer_seconds',
            ]);
    }

    public function test_can_log_game_event(): void
    {
        $session = GameSession::create([
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now(),
            'skor' => 0,
        ]);

        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.games.event', ['kode' => 'pilah-makanan']), [
                'game_session_id' => $session->id,
                'tipe' => 'food_choice',
                'payload' => ['food_id' => 1, 'pilihan' => 'baik', 'benar' => true, 'ms' => 1250],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('game_events', [
            'game_session_id' => $session->id,
            'tipe' => 'food_choice',
        ]);
    }

    public function test_finish_pilah_makanan_clamps_score_and_awards_rewards(): void
    {
        $session = GameSession::create([
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now(),
            'skor' => 0,
        ]);

        // Kirim raw skor 10 (skor sempurna)
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.games.finish', ['kode' => 'pilah-makanan']), [
                'game_session_id' => $session->id,
                'skor' => 10,
                'detail' => ['correct' => 10, 'total' => 10],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'skor' => 10,
                'koin_didapat' => 20, // 2 per benar * 10 = 20
                'xp_didapat' => 10,
                'kebahagiaan_tambah' => 10,
                'kebersihan_tambah' => 0,
            ]);

        $this->pet->refresh();
        $this->assertEquals(60, $this->pet->kebahagiaan);
        $this->assertEquals(20, $this->pet->koin);
        $this->assertEquals(10, $this->pet->xp);
    }

    public function test_score_clamping_prevents_inflated_client_scores(): void
    {
        $session = GameSession::create([
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now(),
            'skor' => 0,
        ]);

        // Mencoba kirim skor curang 999
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.games.finish', ['kode' => 'pilah-makanan']), [
                'game_session_id' => $session->id,
                'skor' => 999,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'skor' => 10, // di-clamp ke 10
                'koin_didapat' => 20,
            ]);
    }

    public function test_daily_coin_cap_limits_coins_but_not_xp(): void
    {
        // Berikan sesi sebelumnya yang sudah mengklaim 24 koin hari ini
        GameSession::create([
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now(),
            'finished_at' => Carbon::now(),
            'skor' => 12, // sum skor = 12
        ]);
        GameSession::create([
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now(),
            'finished_at' => Carbon::now(),
            'skor' => 12, // sum skor = 24
        ]);

        // Sesi baru yang seharusnya dapat 20 koin
        $newSession = GameSession::create([
            'student_id' => $this->student->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now(),
            'skor' => 0,
        ]);

        // Cap harian = 30. Sisa kuota = 30 - 24 = 6 koin
        $response = $this->actingAs($this->student, 'student')
            ->postJson(route('api.games.finish', ['kode' => 'pilah-makanan']), [
                'game_session_id' => $newSession->id,
                'skor' => 10, // potensial 20 koin
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'koin_didapat' => 6, // di-cap ke sisa 6 koin
                'xp_didapat' => 10,   // XP tetap diberikan penuh
            ]);
    }
}
