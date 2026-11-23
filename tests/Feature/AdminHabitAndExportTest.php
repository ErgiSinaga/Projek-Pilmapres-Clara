<?php

namespace Tests\Feature;

use App\Models\HabitAnswer;
use App\Models\HabitCheckin;
use App\Models\HabitRecommendation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminHabitAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $peneliti;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peneliti = User::create([
            'name' => 'Peneliti Utama',
            'email' => 'peneliti@dento.test',
            'username' => 'peneliti',
            'role' => 'peneliti',
            'password' => Hash::make('password123'),
        ]);

        $school = School::create(['kode' => 'SDN1', 'nama' => 'SD Negeri 1 Magelang', 'npsn' => '20123456']);
        $class = SchoolClass::create(['school_id' => $school->id, 'nama' => 'Kelas 4A', 'tingkat' => 4]);

        $this->student = Student::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'kode' => 'SDN1-01',
            'pin_hash' => Hash::make('1234'),
        ]);

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $checkin = HabitCheckin::create([
            'student_id' => $this->student->id,
            'tanggal' => $today,
            'status' => 'selesai',
            'durasi_detik' => 45,
            'started_at' => now()->subMinutes(1),
            'finished_at' => now(),
        ]);

        HabitAnswer::create([
            'checkin_id' => $checkin->id,
            'item_kode' => 'sikat_malam',
            'nilai' => 'belum',
            'nilai_num' => 0,
            'ms' => 1200,
        ]);

        HabitAnswer::create([
            'checkin_id' => $checkin->id,
            'item_kode' => 'sikat_pagi',
            'nilai' => 'sudah',
            'nilai_num' => 1,
            'ms' => 1500,
        ]);

        HabitRecommendation::create([
            'checkin_id' => $checkin->id,
            'student_id' => $this->student->id,
            'tipe' => 'video',
            'target_kode' => 'M1-sikat-malam',
            'alasan_item' => 'sikat_malam',
            'shown_at' => now(),
        ]);
    }

    public function test_admin_habits_dashboard_loads(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->get('/admin/habits');

        $response->assertStatus(200);
        $response->assertSee('Analisis Jurnal Kebiasaan Gigi');
        $response->assertSee('SDN1-01');
    }

    public function test_admin_reopen_checkin(): void
    {
        $response = $this->actingAs($this->peneliti, 'web')
            ->post("/admin/students/{$this->student->id}/reopen-checkin");

        $response->assertRedirect();
        $this->student->refresh();
        $todayCheckin = $this->student->today_checkin;
        $this->assertEquals('draft', $todayCheckin->status);
        $this->assertNull($todayCheckin->finished_at);
    }

    public function test_csv_exports_contain_habit_data(): void
    {
        // 1. Kebiasaan Harian
        $resHarian = $this->actingAs($this->peneliti, 'web')
            ->get('/admin/export/kebiasaan-harian');
        $resHarian->assertStatus(200);
        $this->assertStringContainsString('SDN1-01', $resHarian->streamedContent());
        $this->assertStringContainsString('sikat_malam', $resHarian->streamedContent());

        // 2. Kebiasaan Ringkas
        $resRingkas = $this->actingAs($this->peneliti, 'web')
            ->get('/admin/export/kebiasaan-ringkas');
        $resRingkas->assertStatus(200);
        $this->assertStringContainsString('SDN1-01', $resRingkas->streamedContent());

        // 3. Rekomendasi Kebiasaan
        $resRec = $this->actingAs($this->peneliti, 'web')
            ->get('/admin/export/rekomendasi-kebiasaan');
        $resRec->assertStatus(200);
        $this->assertStringContainsString('M1-sikat-malam', $resRec->streamedContent());

        // 4. Aktivitas dengan kolom sumber
        $resAktivitas = $this->actingAs($this->peneliti, 'web')
            ->get('/admin/export/aktivitas');
        $resAktivitas->assertStatus(200);
        $this->assertStringContainsString('sumber', $resAktivitas->streamedContent());
    }
}
