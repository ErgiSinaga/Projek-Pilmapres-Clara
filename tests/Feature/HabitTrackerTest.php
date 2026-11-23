<?php

namespace Tests\Feature;

use App\Models\PetState;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TestAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HabitTrackerTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['kode' => 'SDN1', 'nama' => 'SD Negeri 1 Magelang', 'npsn' => '20123456']);
        $class = SchoolClass::create(['school_id' => $school->id, 'nama' => 'Kelas 4A', 'tingkat' => 4]);

        $this->student = Student::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'kode' => 'STD-TEST-01',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => 'Dento Hero',
        ]);

        PetState::create([
            'student_id' => $this->student->id,
            'kebersihan' => 70,
            'kebahagiaan' => 70,
            'koin' => 10,
            'xp' => 0,
            'level' => 1,
        ]);
    }

    public function test_jurnal_blocked_before_pre_test_completed(): void
    {
        $response = $this->actingAs($this->student, 'student')
            ->getJson('/api/jurnal/hari-ini');

        $response->assertStatus(403);
        $response->assertJsonFragment(['unlocked' => false]);
    }

    public function test_jurnal_accessible_after_pre_test_completed(): void
    {
        TestAttempt::create([
            'student_id' => $this->student->id,
            'jenis' => 'pre',
            'set_soal' => 'A',
            'skor' => 80,
            'finished_at' => now(),
        ]);

        $response = $this->actingAs($this->student, 'student')
            ->getJson('/api/jurnal/hari-ini');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tanggal',
            'status',
            'items',
            'selesai',
        ]);
    }

    public function test_autosave_answers_and_fair_reward_isolation(): void
    {
        TestAttempt::create([
            'student_id' => $this->student->id,
            'jenis' => 'pre',
            'set_soal' => 'A',
            'skor' => 80,
            'finished_at' => now(),
        ]);

        // 1. Jawab pertanyaan
        $ans1 = $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/jawab', [
                'item_kode' => 'sikat_pagi',
                'nilai' => 'belum', // Menjawab "belum"
                'ms' => 1200,
            ]);
        $ans1->assertStatus(200);
        $ans1->assertJson(['success' => true]);

        $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/jawab', ['item_kode' => 'sikat_malam', 'nilai' => 'belum']);

        $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/jawab', ['item_kode' => 'manis', 'nilai' => '0']);

        // manis = 0, kumur tidak wajib (tampil_jika = 1, 2, 3_plus)
        $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/jawab', ['item_kode' => 'air_putih', 'nilai' => 'sudah']);

        // 2. Selesaikan jurnal
        $initialCleanliness = $this->student->petState->kebersihan;
        $initialCoins = $this->student->petState->koin;
        $initialXp = $this->student->petState->xp;

        $finish = $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/selesai');

        $finish->assertStatus(200);
        $finish->assertJson(['success' => true]);

        // Cek hadiah flat koin 8, xp 10, kebahagiaan +5
        $this->student->petState->refresh();
        $this->assertEquals($initialCoins + 8, $this->student->petState->koin);
        $this->assertEquals($initialXp + 10, $this->student->petState->xp);

        // ISOLASI STATUS HEWAN: Kebersihan TIDAK BOLEH berubah karena isi laporan kebiasaan!
        $this->assertEquals($initialCleanliness, $this->student->petState->kebersihan);

        // IMMUTABILITY: Setelah selesai, tidak boleh bisa mengubah jawaban lagi
        $editAttempt = $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/jawab', [
                'item_kode' => 'sikat_pagi',
                'nilai' => 'sudah',
            ]);
        $editAttempt->assertStatus(409);
    }

    public function test_lewati_increments_dilewati_count(): void
    {
        TestAttempt::create([
            'student_id' => $this->student->id,
            'jenis' => 'pre',
            'set_soal' => 'A',
            'skor' => 80,
            'finished_at' => now(),
        ]);

        $res = $this->actingAs($this->student, 'student')
            ->postJson('/api/jurnal/lewati');

        $res->assertStatus(200);
        $res->assertJson(['success' => true, 'dilewati_count' => 1]);

        $this->assertDatabaseHas('habit_checkins', [
            'student_id' => $this->student->id,
            'dilewati_count' => 1,
            'status' => 'dilewati',
        ]);
    }

    public function test_ringkasan_displays_14_days_and_badges(): void
    {
        TestAttempt::create([
            'student_id' => $this->student->id,
            'jenis' => 'pre',
            'set_soal' => 'A',
            'skor' => 80,
            'finished_at' => now(),
        ]);

        $res = $this->actingAs($this->student, 'student')
            ->getJson('/api/jurnal/ringkasan');

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'kalender',
            'rekap_7_hari',
            'streak',
            'total_checkin',
            'lencana',
        ]);
    }
}
