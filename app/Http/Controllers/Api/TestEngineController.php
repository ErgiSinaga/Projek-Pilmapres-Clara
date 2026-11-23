<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\TestItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestEngineController extends Controller
{
    /**
     * Memulai atau melanjutkan sesi pre-test / post-test.
     */
    public function start(Request $request, string $jenis): JsonResponse
    {
        if (! in_array($jenis, ['pre', 'post'], true)) {
            return response()->json(['message' => 'Jenis tes tidak valid.'], 400);
        }

        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['message' => 'Belum masuk.'], 401);
        }

        // Cek syarat pembukaan post-test jika jenis = post
        if ($jenis === 'post') {
            $isUnlocked = $this->checkPostTestUnlocked($student);
            if (! $isUnlocked) {
                return response()->json([
                    'message' => 'Post-test belum terbuka. Selesaikan aktivitas belajar dan video terlebih dahulu ya, atau tunggu instruksi dari gurumu.',
                    'unlocked' => false,
                ], 403);
            }
        }

        // Cek attempt yang sudah ada
        $attempt = TestAttempt::where('student_id', $student->id)
            ->where('jenis', $jenis)
            ->first();

        if ($attempt && $attempt->finished_at !== null) {
            return response()->json([
                'message' => 'Kamu sudah menyelesaikan tes ini. Tes hanya dapat dikerjakan satu kali.',
                'is_completed' => true,
            ], 403);
        }

        $setConfig = config("dento.test_set_{$jenis}", 'A');
        $items = TestItem::where('set', $setConfig)->orderBy('urutan')->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Soal tes belum tersedia di sistem.'], 404);
        }

        if (! $attempt) {
            $attempt = TestAttempt::create([
                'student_id' => $student->id,
                'jenis' => $jenis,
                'started_at' => Carbon::now(),
                'skor' => 0,
                'total' => $items->count(),
            ]);
        }

        // Ambil jawaban yang sudah ada untuk resume
        $existingAnswers = TestAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('test_item_id');

        $questionsPayload = [];
        $currentIndex = 0;
        $foundNextUnanswered = false;

        foreach ($items as $idx => $item) {
            $answered = $existingAnswers->has($item->id);
            $rawOptions = $item->opsi;

            if ($answered) {
                // Gunakan urutan yang sudah tersimpan
                $storedAnswer = $existingAnswers->get($item->id);
                $shuffledIndices = $storedAnswer->opsi_urutan ?? [0, 1, 2];
            } else {
                // Acak urutan deterministik berdasarkan attempt_id dan item_id agar stabil saat refresh
                $seed = (int) ($attempt->id * 1000 + $item->id);
                mt_srand($seed);
                $indices = range(0, count($rawOptions) - 1);
                shuffle($indices);
                $shuffledIndices = $indices;
                mt_srand(); // reset seed

                if (! $foundNextUnanswered) {
                    $currentIndex = $idx;
                    $foundNextUnanswered = true;
                }
            }

            $displayOptions = [];
            foreach ($shuffledIndices as $origIdx) {
                $displayOptions[] = $rawOptions[$origIdx] ?? '';
            }

            $questionsPayload[] = [
                'id' => $item->id,
                'urutan' => $item->urutan,
                'tp' => $item->tp,
                'teks' => $item->teks,
                'opsi' => $displayOptions,
                'opsi_urutan' => $shuffledIndices, // peta indeks asli
                'gambar' => $item->gambar,
                'audio' => $item->audio && config('dento.audio_enabled', true) ? $item->audio : null,
                'answered' => $answered,
                'selected_option' => $answered ? $existingAnswers->get($item->id)->pilihan : null,
            ];
        }

        if (! $foundNextUnanswered && $existingAnswers->count() < $items->count()) {
            $currentIndex = $existingAnswers->count();
        }

        return response()->json([
            'attempt_id' => $attempt->id,
            'jenis' => $jenis,
            'current_index' => $currentIndex,
            'total_items' => $items->count(),
            'answered_count' => $existingAnswers->count(),
            'questions' => $questionsPayload,
        ]);
    }

    /**
     * Menyimpan jawaban per butir soal seketika.
     */
    public function answer(Request $request, string $jenis): JsonResponse
    {
        $validated = $request->validate([
            'test_item_id' => ['required', 'exists:test_items,id'],
            'pilihan' => ['required', 'integer', 'min:0', 'max:5'],
            'ms' => ['nullable', 'integer', 'min:0'],
            'opsi_urutan' => ['required', 'array'],
        ]);

        $student = Auth::guard('student')->user();
        $attempt = TestAttempt::where('student_id', $student->id)
            ->where('jenis', $jenis)
            ->whereNull('finished_at')
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'Sesi tes tidak aktif atau sudah selesai.'], 403);
        }

        // Cek jika butir ini sudah pernah dijawab
        $existing = TestAnswer::where('attempt_id', $attempt->id)
            ->where('test_item_id', $validated['test_item_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Soal ini sudah dijawab sebelumnya dan tidak dapat diubah.',
                'answered' => true,
            ], 400);
        }

        $testItem = TestItem::findOrFail($validated['test_item_id']);
        $opsiUrutan = $validated['opsi_urutan'];
        $chosenDisplayIdx = (int) $validated['pilihan'];

        // Dapatkan indeks asli dari opsi yang dipilih
        $chosenOriginalIdx = $opsiUrutan[$chosenDisplayIdx] ?? $chosenDisplayIdx;
        $isCorrect = ($chosenOriginalIdx === (int) $testItem->benar);

        TestAnswer::create([
            'attempt_id' => $attempt->id,
            'test_item_id' => $testItem->id,
            'pilihan' => $chosenDisplayIdx,
            'benar' => $isCorrect,
            'ms' => $validated['ms'] ?? 0,
            'opsi_urutan' => $opsiUrutan,
        ]);

        if ($isCorrect) {
            $attempt->increment('skor');
        }

        $answeredCount = TestAnswer::where('attempt_id', $attempt->id)->count();

        // Kunci jawaban tidak pernah dibocorkan ke klien
        return response()->json([
            'success' => true,
            'answered_count' => $answeredCount,
            'total_items' => $attempt->total,
        ]);
    }

    /**
     * Mengunci dan menyelesaikan sesi tes.
     */
    public function finish(Request $request, string $jenis): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $attempt = TestAttempt::where('student_id', $student->id)
            ->where('jenis', $jenis)
            ->whereNull('finished_at')
            ->first();

        if (! $attempt) {
            return response()->json(['message' => 'Tidak ada sesi tes yang sedang berjalan.'], 404);
        }

        $now = Carbon::now();
        $durasi = (int) $attempt->started_at->diffInSeconds($now);

        $attempt->update([
            'finished_at' => $now,
            'durasi_detik' => $durasi,
        ]);

        // Berikan XP reward tes ke hewan peliharaan
        if ($student->petState) {
            $student->petState->addXp(config('dento.xp.test', 30));
        }

        // Tampilkan hanya bintang agar tidak membebani anak
        return response()->json([
            'message' => 'Hebat! Kamu telah menyelesaikan tes dengan sangat baik.',
            'stars' => 3,
            'is_finished' => true,
        ]);
    }

    /**
     * Kuesioner Kepuasan Murid (skala 1-5).
     */
    public function getSurvey(): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $responses = SurveyResponse::where('student_id', $student->id)
            ->pluck('nilai', 'butir_kode');

        $questions = [
            ['kode' => 'K1', 'teks' => 'Aku suka bermain dan belajar bersama Dento.'],
            ['kode' => 'K2', 'teks' => 'Aku mudah mengerti cerita dan video dokter gigi di Dento.'],
            ['kode' => 'K3', 'teks' => 'Aku ingin merawat Dento lagi di sekolah atau di rumah.'],
            ['kode' => 'K4', 'teks' => 'Game dan aktivitas di Dento sangat mudah dimainkan.'],
            ['kode' => 'K5', 'teks' => 'Setelah bermain Dento, aku jadi tahu cara menyikat gigi yang benar.'],
            ['kode' => 'K6', 'teks' => 'Aku sangat menyukai Dento sebagai pahlawan gigi sehatku.'],
        ];

        return response()->json([
            'questions' => $questions,
            'responses' => $responses,
            'is_completed' => $responses->count() >= count($questions),
        ]);
    }

    public function submitSurvey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'responses' => ['required', 'array'],
            'responses.*.kode' => ['required', 'string'],
            'responses.*.nilai' => ['required', 'integer', 'between:1,5'],
        ]);

        $student = Auth::guard('student')->user();

        foreach ($validated['responses'] as $resp) {
            SurveyResponse::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'butir_kode' => $resp['kode'],
                ],
                [
                    'nilai' => $resp['nilai'],
                ]
            );
        }

        return response()->json([
            'message' => 'Terima kasih banyak sudah mengisi kuesioner kepuasan bersama Dento!',
            'success' => true,
        ]);
    }

    /**
     * Aturan unlock post-test dari konfigurasi dento.php.
     */
    private function checkPostTestUnlocked($student): bool
    {
        // 1. Cek jika sudah ada attempt post yang dibuat oleh guru dari admin
        $existingAttempt = TestAttempt::where('student_id', $student->id)
            ->where('jenis', 'post')
            ->first();

        if ($existingAttempt) {
            return true;
        }

        $config = config('dento.post_test_unlock');
        if (($config['mode'] ?? 'auto') === 'manual') {
            return false;
        }

        // Mode auto: periksa syarat hari aktif, video selesai, level, dan check-in jurnal
        $activeDays = $student->active_days_count;
        $videosCount = $student->videoProgress()->whereNotNull('selesai_at')->count();
        $petLevel = $student->petState->level ?? 1;
        $checkinsDone = $student->completed_checkins_count;

        return $activeDays >= ($config['min_active_days'] ?? 3)
            && $videosCount >= ($config['min_videos'] ?? 4)
            && $petLevel >= ($config['min_level'] ?? 3)
            && $checkinsDone >= ($config['min_checkins'] ?? 3);
    }
}
