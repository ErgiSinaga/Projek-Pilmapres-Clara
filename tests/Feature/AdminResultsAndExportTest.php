<?php

namespace Tests\Feature;

use App\Models\GameEvent;
use App\Models\GameSession;
use App\Models\LoginLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SurveyResponse;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\TestItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResultsAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $peneliti;

    private User $guru;

    private School $school1;

    private School $school2;

    private Student $student1;

    private Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school1 = School::create(['nama' => 'SD Negeri 1 Magelang', 'kode' => 'S1']);
        $this->school2 = School::create(['nama' => 'SD Negeri 2 Magelang', 'kode' => 'S2']);

        $class1 = SchoolClass::create(['school_id' => $this->school1->id, 'nama' => 'Kelas 4A', 'tingkat' => 4]);
        $class2 = SchoolClass::create(['school_id' => $this->school2->id, 'nama' => 'Kelas 4B', 'tingkat' => 4]);

        $this->peneliti = User::create([
            'name' => 'Dr. Peneliti Gigi',
            'email' => 'peneliti@dento.test',
            'password' => Hash::make('password123'),
            'role' => 'peneliti',
        ]);

        $this->guru = User::create([
            'name' => 'Ibu Guru Ani',
            'email' => 'guru@dento.test',
            'password' => Hash::make('password123'),
            'role' => 'guru',
            'school_id' => $this->school1->id,
        ]);

        $this->student1 = Student::create([
            'school_id' => $this->school1->id,
            'class_id' => $class1->id,
            'kode' => 'S1-01',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => 'Dento Juara',
        ]);

        $this->student2 = Student::create([
            'school_id' => $this->school2->id,
            'class_id' => $class2->id,
            'kode' => 'S2-01',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => 'Dento Bintang',
        ]);

        // Buat soal tes item
        $item = TestItem::create([
            'set' => 'A',
            'urutan' => 1,
            'tp' => 'TP1',
            'teks' => 'Kapan waktu menyikat gigi malam hari?',
            'opsi' => ['Sebelum tidur', 'Sebelum makan'],
            'benar' => 0,
            'status' => 'aktif',
        ]);

        // Buat percobaan pre & post untuk student1
        $preAttempt = TestAttempt::create([
            'student_id' => $this->student1->id,
            'jenis' => 'pre',
            'started_at' => Carbon::now()->subDays(3),
            'finished_at' => Carbon::now()->subDays(3)->addMinutes(5),
            'skor' => 5,
            'total' => 10,
            'durasi_detik' => 300,
        ]);

        TestAnswer::create([
            'attempt_id' => $preAttempt->id,
            'test_item_id' => $item->id,
            'pilihan' => 0,
            'benar' => true,
            'ms' => 12000,
            'opsi_urutan' => [0, 1],
        ]);

        $postAttempt = TestAttempt::create([
            'student_id' => $this->student1->id,
            'jenis' => 'post',
            'started_at' => Carbon::now()->subHour(),
            'finished_at' => Carbon::now()->subMinutes(55),
            'skor' => 9,
            'total' => 10,
            'durasi_detik' => 300,
        ]);

        TestAnswer::create([
            'attempt_id' => $postAttempt->id,
            'test_item_id' => $item->id,
            'pilihan' => 0,
            'benar' => true,
            'ms' => 8000,
            'opsi_urutan' => [0, 1],
        ]);

        // Data game session & events
        $session = GameSession::create([
            'student_id' => $this->student1->id,
            'game_kode' => 'pilah-makanan',
            'started_at' => Carbon::now()->subDays(1),
            'finished_at' => Carbon::now()->subDays(1)->addMinutes(3),
            'skor' => 10,
        ]);

        GameEvent::create([
            'game_session_id' => $session->id,
            'student_id' => $this->student1->id,
            'tipe' => 'food_choice',
            'payload' => ['food_id' => 1, 'nama' => 'Apel Segar', 'pilihan' => 'baik', 'benar' => true, 'ms' => 1500],
        ]);

        // Data survei
        SurveyResponse::create([
            'student_id' => $this->student1->id,
            'butir_kode' => 'Q1',
            'nilai' => 5,
        ]);

        // Data login log
        LoginLog::create([
            'student_id' => $this->student1->id,
            'login_at' => Carbon::now()->subDays(1),
        ]);
    }

    public function test_peneliti_can_view_results_page(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.results.index'));

        $response->assertStatus(200)
            ->assertSee('Hasil Evaluasi Pre-Test vs Post-Test')
            ->assertSee('S1-01')
            ->assertSee('Rata-rata N-Gain');
    }

    public function test_guru_can_only_view_results_for_their_school(): void
    {
        $response = $this->actingAs($this->guru, 'web')
            ->get(route('admin.results.index'));

        $response->assertStatus(200)
            ->assertSee('S1-01')
            ->assertDontSee('S2-01'); // Murid sekolah lain tidak boleh terlihat
    }

    public function test_export_hasil_tes_contains_utf8_bom_and_no_names(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.export.hasil-tes'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Cek UTF-8 BOM
        $bom = chr(0xEF).chr(0xBB).chr(0xBF);
        $this->assertStringStartsWith($bom, $content);

        // Cek header dan kode peserta
        $this->assertStringContainsString('kode_peserta', $content);
        $this->assertStringContainsString('S1-01', $content);
        $this->assertStringContainsString('selisih', $content);
        $this->assertStringContainsString('n_gain', $content);

        // Pastikan tidak ada nama asli
        $this->assertStringNotContainsString('Dr. Peneliti', $content);
        $this->assertStringNotContainsString('Ibu Guru Ani', $content);
    }

    public function test_export_jawaban_butir(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.export.jawaban-butir'));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('kode_peserta,jenis_tes,nomor_soal,tp', $content);
        $this->assertStringContainsString('S1-01,pre,1,TP1', $content);
    }

    public function test_export_aktivitas(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.export.aktivitas'));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('kode_peserta,tipe_aktivitas,aktivitas_kode', $content);
        $this->assertStringContainsString('S1-01,game,pilah-makanan', $content);
    }

    public function test_export_pilihan_makanan(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.export.pilihan-makanan'));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('kode_peserta,sesi_id,food_id,nama_makanan', $content);
        $this->assertStringContainsString('S1-01', $content);
        $this->assertStringContainsString('Apel Segar', $content);
    }

    public function test_export_survei(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.export.survei'));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('kode_peserta,q1,q2,q3,q4,q5,q6', $content);
        $this->assertStringContainsString('S1-01', $content);
    }

    public function test_export_login_hari_aktif(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get(route('admin.export.login-hari-aktif'));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('kode_peserta,tanggal,jumlah_login,total_durasi_menit', $content);
        $this->assertStringContainsString('S1-01', $content);
    }
}
