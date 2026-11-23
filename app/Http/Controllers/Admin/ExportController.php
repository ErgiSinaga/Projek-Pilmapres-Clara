<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameEvent;
use App\Models\GameSession;
use App\Models\HabitCheckin;
use App\Models\HabitRecommendation;
use App\Models\LoginLog;
use App\Models\Student;
use App\Models\TestAnswer;
use App\Models\VideoProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private function getScopedStudents(Request $request)
    {
        $user = Auth::guard('web')->user();
        $query = Student::query()->with(['school', 'schoolClass']);

        if ($user->isGuru()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        return $query->get();
    }

    private function streamCsv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');
            // Tulis UTF-8 BOM agar rapi di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 1. hasil_tes.csv: kode_peserta, sekolah, kelas, pre_skor, pre_selesai_at, post_skor, post_selesai_at, selisih, n_gain
     */
    public function hasilTes(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request);
        $header = ['kode_peserta', 'sekolah', 'kelas', 'pre_skor', 'pre_selesai_at', 'post_skor', 'post_selesai_at', 'selisih', 'n_gain'];

        $rows = [];
        foreach ($students as $student) {
            $pre = $student->testAttempts()->where('jenis', 'pre')->whereNotNull('finished_at')->first();
            $post = $student->testAttempts()->where('jenis', 'post')->whereNotNull('finished_at')->first();

            $preScore = $pre ? $pre->skor : '';
            $postScore = $post ? $post->skor : '';
            $preFinishedAt = $pre ? $pre->finished_at?->format('Y-m-d H:i:s') : '';
            $postFinishedAt = $post ? $post->finished_at?->format('Y-m-d H:i:s') : '';

            $delta = '';
            $nGain = '';

            if ($pre && $post) {
                $delta = $post->skor - $pre->skor;
                $totalMax = $pre->total ?: 10;
                if ($totalMax > $pre->skor) {
                    $nGain = round(($post->skor - $pre->skor) / ($totalMax - $pre->skor), 3);
                } else {
                    $nGain = ($post->skor >= $pre->skor) ? 1.0 : 0.0;
                }
            }

            $rows[] = [
                $student->kode,
                $student->school?->nama ?? '',
                $student->schoolClass?->nama ?? '',
                $preScore,
                $preFinishedAt,
                $postScore,
                $postFinishedAt,
                $delta,
                $nGain,
            ];
        }

        return $this->streamCsv('hasil_tes.csv', $header, $rows);
    }

    /**
     * 2. jawaban_butir.csv: kode_peserta, jenis_tes, nomor_soal, tp, jawaban_dipilih, kunci_jawaban, is_benar, durasi_detik
     */
    public function jawabanButir(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request)->keyBy('id');
        $studentIds = $students->keys()->all();

        $header = ['kode_peserta', 'jenis_tes', 'nomor_soal', 'tp', 'jawaban_dipilih', 'kunci_jawaban', 'is_benar', 'durasi_detik'];

        $answers = TestAnswer::whereHas('attempt', fn ($q) => $q->whereIn('student_id', $studentIds))
            ->with(['attempt', 'testItem'])
            ->get();

        $rows = [];
        foreach ($answers as $ans) {
            $student = $students->get($ans->attempt?->student_id);
            if (! $student) {
                continue;
            }

            $rows[] = [
                $student->kode,
                $ans->attempt?->jenis ?? '',
                $ans->testItem?->urutan ?? '',
                $ans->testItem?->tp ?? '',
                $ans->pilihan,
                $ans->testItem?->benar ?? '',
                $ans->benar ? 1 : 0,
                round(($ans->ms ?? 0) / 1000, 2),
            ];
        }

        return $this->streamCsv('jawaban_butir.csv', $header, $rows);
    }

    /**
     * 3. aktivitas.csv: kode_peserta, aktivitas_kode, waktu_mulai, waktu_selesai, skor, durasi_detik
     */
    public function aktivitas(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request)->keyBy('id');
        $studentIds = $students->keys()->all();

        $header = ['kode_peserta', 'tipe_aktivitas', 'aktivitas_kode', 'waktu_mulai', 'waktu_selesai', 'skor', 'durasi_detik', 'sumber'];

        $sessions = GameSession::whereIn('student_id', $studentIds)->orderBy('started_at')->get();
        $videoProgresses = VideoProgress::whereIn('student_id', $studentIds)->orderBy('created_at')->get();

        $rows = [];
        foreach ($sessions as $ses) {
            $student = $students->get($ses->student_id);
            if (! $student) {
                continue;
            }

            $duration = 0;
            if ($ses->started_at && $ses->finished_at) {
                $duration = $ses->finished_at->diffInSeconds($ses->started_at);
            }

            $rows[] = [
                $student->kode,
                'game',
                $ses->game_kode,
                $ses->started_at?->format('Y-m-d H:i:s') ?? '',
                $ses->finished_at?->format('Y-m-d H:i:s') ?? '',
                $ses->skor,
                $duration,
                'main',
            ];
        }

        foreach ($videoProgresses as $vp) {
            $student = $students->get($vp->student_id);
            if (! $student) {
                continue;
            }

            $rows[] = [
                $student->kode,
                'video',
                $vp->video_id,
                $vp->created_at?->format('Y-m-d H:i:s') ?? '',
                $vp->selesai_at?->format('Y-m-d H:i:s') ?? '',
                $vp->skor_tantangan ?? '',
                $vp->detik_ditonton,
                $vp->sumber ?? 'kelas',
            ];
        }

        return $this->streamCsv('aktivitas.csv', $header, $rows);
    }

    /**
     * 4. pilihan_makanan.csv: kode_peserta, sesi_id, food_id, nama_makanan, kategori, pilihan_murid, is_benar, waktu_pikir_ms
     */
    public function pilihanMakanan(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request)->keyBy('id');
        $studentIds = $students->keys()->all();

        $header = ['kode_peserta', 'sesi_id', 'food_id', 'nama_makanan', 'kategori', 'pilihan_murid', 'is_benar', 'waktu_pikir_ms'];

        $foodsFile = database_path('content/foods.json');
        $foods = File::exists($foodsFile) ? collect(json_decode(File::get($foodsFile), true))->keyBy('id') : collect();

        $events = GameEvent::whereIn('student_id', $studentIds)
            ->where('tipe', 'food_choice')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($events as $ev) {
            $student = $students->get($ev->student_id);
            if (! $student) {
                continue;
            }

            $payload = $ev->payload ?? [];
            $foodId = $payload['food_id'] ?? null;
            $foodMeta = $foods->get($foodId);

            $rows[] = [
                $student->kode,
                $ev->game_session_id,
                $foodId ?? '',
                $foodMeta['nama'] ?? ($payload['nama'] ?? ''),
                $foodMeta['kategori'] ?? '',
                $payload['pilihan'] ?? '',
                ! empty($payload['benar']) ? 1 : 0,
                $payload['ms'] ?? 0,
            ];
        }

        return $this->streamCsv('pilihan_makanan.csv', $header, $rows);
    }

    /**
     * 5. survei.csv: kode_peserta, q1, q2, q3, q4, q5, q6, skor_total, diisi_at
     */
    public function survei(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request);
        $header = ['kode_peserta', 'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'skor_total', 'diisi_at'];

        $rows = [];
        foreach ($students as $student) {
            $responses = $student->surveyResponses()->get()->keyBy('butir_kode');
            if ($responses->isEmpty()) {
                continue;
            }

            $q1 = $responses->get('Q1')?->nilai ?? '';
            $q2 = $responses->get('Q2')?->nilai ?? '';
            $q3 = $responses->get('Q3')?->nilai ?? '';
            $q4 = $responses->get('Q4')?->nilai ?? '';
            $q5 = $responses->get('Q5')?->nilai ?? '';
            $q6 = $responses->get('Q6')?->nilai ?? '';

            $total = 0;
            $count = 0;
            foreach (['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6'] as $k) {
                if ($responses->has($k)) {
                    $total += $responses->get($k)->nilai;
                    $count++;
                }
            }

            $diisiAt = $responses->first()?->created_at?->format('Y-m-d H:i:s') ?? '';

            $rows[] = [
                $student->kode,
                $q1,
                $q2,
                $q3,
                $q4,
                $q5,
                $q6,
                $count > 0 ? $total : '',
                $diisiAt,
            ];
        }

        return $this->streamCsv('survei.csv', $header, $rows);
    }

    /**
     * 6. login_hari_aktif.csv: kode_peserta, tanggal, jumlah_login, total_durasi_menit
     */
    public function loginHariAktif(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request)->keyBy('id');
        $studentIds = $students->keys()->all();

        $header = ['kode_peserta', 'tanggal', 'jumlah_login', 'total_durasi_menit'];

        $logs = LoginLog::whereIn('student_id', $studentIds)
            ->selectRaw('student_id, DATE(login_at) as tgl, COUNT(*) as jml')
            ->groupBy('student_id', 'tgl')
            ->orderBy('tgl')
            ->get();

        $rows = [];
        foreach ($logs as $log) {
            $student = $students->get($log->student_id);
            if (! $student) {
                continue;
            }

            // Estimasi durasi per hari aktif dari game sessions pada hari itu
            $sessionDuration = GameSession::where('student_id', $student->id)
                ->whereDate('started_at', $log->tgl)
                ->whereNotNull('finished_at')
                ->get()
                ->sum(fn ($s) => $s->finished_at->diffInMinutes($s->started_at));

            $rows[] = [
                $student->kode,
                $log->tgl,
                $log->jml,
                max(5, (int) $sessionDuration), // estimasi minimal 5 menit per hari aktif
            ];
        }

        return $this->streamCsv('login_hari_aktif.csv', $header, $rows);
    }

    /**
     * 7. kebiasaan_harian.csv: kode_peserta, sekolah, kelas, tanggal_lapor, item_kode, nilai, nilai_num, acuan, waktu_respons_ms, waktu_lapor
     */
    public function kebiasaanHarian(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request)->keyBy('id');
        $studentIds = $students->keys()->all();

        $header = ['kode_peserta', 'sekolah', 'kelas', 'tanggal_lapor', 'item_kode', 'nilai', 'nilai_num', 'acuan', 'waktu_respons_ms', 'waktu_lapor'];

        $habitsFile = database_path('content/habits.json');
        $habits = File::exists($habitsFile) ? collect(json_decode(File::get($habitsFile), true)['items'] ?? [])->keyBy('kode') : collect();

        $checkins = HabitCheckin::with('answers')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'selesai')
            ->orderBy('tanggal')
            ->get();

        $rows = [];
        foreach ($checkins as $ch) {
            $student = $students->get($ch->student_id);
            if (! $student) {
                continue;
            }

            foreach ($ch->answers as $ans) {
                $itemMeta = $habits->get($ans->item_kode);
                $acuan = $itemMeta['acuan'] ?? 'hari_ini';

                $rows[] = [
                    $student->kode,
                    $student->school?->nama ?? '',
                    $student->schoolClass?->nama ?? '',
                    $ch->tanggal?->format('Y-m-d') ?? '',
                    $ans->item_kode,
                    $ans->nilai,
                    $ans->nilai_num !== null ? $ans->nilai_num : '',
                    $acuan,
                    $ans->ms ?? '',
                    $ans->answered_at?->format('Y-m-d H:i:s') ?? '',
                ];
            }
        }

        return $this->streamCsv('kebiasaan_harian.csv', $header, $rows);
    }

    /**
     * 8. kebiasaan_ringkas.csv: kode_peserta, sekolah, kelas, total_hari_lapor, sikat_pagi_proporsi, sikat_malam_proporsi, manis_mean, kumur_proporsi, air_putih_proporsi, streak_tertinggi
     */
    public function kebiasaanRingkas(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request);
        $header = ['kode_peserta', 'sekolah', 'kelas', 'total_hari_lapor', 'sikat_pagi_proporsi', 'sikat_malam_proporsi', 'manis_mean', 'kumur_proporsi', 'air_putih_proporsi', 'streak_tertinggi'];

        $rows = [];
        foreach ($students as $student) {
            $checkins = $student->habitCheckins()->with('answers')->where('status', 'selesai')->get();
            $totalDays = $checkins->count();

            if ($totalDays === 0) {
                $rows[] = [
                    $student->kode,
                    $student->school?->nama ?? '',
                    $student->schoolClass?->nama ?? '',
                    0, '', '', '', '', '', 0,
                ];

                continue;
            }

            $allAnswers = $checkins->flatMap->answers;
            $sp = $allAnswers->where('item_kode', 'sikat_pagi');
            $sm = $allAnswers->where('item_kode', 'sikat_malam');
            $mn = $allAnswers->where('item_kode', 'manis');
            $km = $allAnswers->where('item_kode', 'kumur')->where('nilai', '!=', 'tidak_berlaku');
            $ap = $allAnswers->where('item_kode', 'air_putih');

            $spProp = $sp->count() > 0 ? round($sp->where('nilai', 'sudah')->count() / $sp->count(), 3) : '';
            $smProp = $sm->count() > 0 ? round($sm->where('nilai', 'sudah')->count() / $sm->count(), 3) : '';
            $mnMean = $mn->count() > 0 ? round($mn->avg('nilai_num'), 2) : '';
            $kmProp = $km->count() > 0 ? round($km->where('nilai', 'ya')->count() / $km->count(), 3) : '';
            $apProp = $ap->count() > 0 ? round($ap->where('nilai', 'sudah')->count() / $ap->count(), 3) : '';
            $streak = $student->school_days_streak;

            $rows[] = [
                $student->kode,
                $student->school?->nama ?? '',
                $student->schoolClass?->nama ?? '',
                $totalDays,
                $spProp,
                $smProp,
                $mnMean,
                $kmProp,
                $apProp,
                $streak,
            ];
        }

        return $this->streamCsv('kebiasaan_ringkas.csv', $header, $rows);
    }

    /**
     * 9. rekomendasi_kebiasaan.csv: kode_peserta, tanggal_checkin, alasan_item, tipe_rekomendasi, target_kode, ditampilkan_at, diterima_at, dituntaskan_at
     */
    public function rekomendasiKebiasaan(Request $request): StreamedResponse
    {
        $students = $this->getScopedStudents($request)->keyBy('id');
        $studentIds = $students->keys()->all();

        $header = ['kode_peserta', 'tanggal_checkin', 'alasan_item', 'tipe_rekomendasi', 'target_kode', 'ditampilkan_at', 'diterima_at', 'dituntaskan_at'];

        $recs = HabitRecommendation::with('checkin')
            ->whereIn('student_id', $studentIds)
            ->orderBy('shown_at')
            ->get();

        $rows = [];
        foreach ($recs as $rec) {
            $student = $students->get($rec->student_id);
            if (! $student) {
                continue;
            }

            $rows[] = [
                $student->kode,
                $rec->checkin?->tanggal?->format('Y-m-d') ?? '',
                $rec->alasan_item,
                $rec->tipe,
                $rec->target_kode,
                $rec->shown_at?->format('Y-m-d H:i:s') ?? '',
                $rec->accepted_at?->format('Y-m-d H:i:s') ?? '',
                $rec->completed_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        return $this->streamCsv('rekomendasi_kebiasaan.csv', $header, $rows);
    }
}
