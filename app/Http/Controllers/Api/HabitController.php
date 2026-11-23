<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HabitAnswer;
use App\Models\HabitCheckin;
use App\Models\HabitRecommendation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class HabitController extends Controller
{
    /**
     * Memeriksa apakah murid sudah menyelesaikan pre-test.
     */
    private function checkPreTestCompleted($student): bool
    {
        return $student->testAttempts()
            ->where('jenis', 'pre')
            ->whereNotNull('finished_at')
            ->exists();
    }

    /**
     * Membaca konten habits.json.
     */
    private function getHabitsContent(): array
    {
        $path = database_path('content/habits.json');
        if (! File::exists($path)) {
            return ['items' => []];
        }

        return json_decode(File::get($path), true) ?? ['items' => []];
    }

    /**
     * Data check-in hari ini beserta pertanyaan dan jawaban tersimpan.
     */
    public function hariIni(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['message' => 'Belum masuk.'], 401);
        }

        if (! $this->checkPreTestCompleted($student)) {
            return response()->json([
                'message' => 'Jurnal gigi baru tersedia setelah kamu menyelesaikan Pre-Test.',
                'unlocked' => false,
            ], 403);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $checkin = HabitCheckin::firstOrCreate(
            ['student_id' => $student->id, 'tanggal' => $today],
            ['status' => 'draft', 'started_at' => now()]
        );

        $answers = $checkin->answers->keyBy('item_kode');
        $habitsData = $this->getHabitsContent();
        $allItems = $habitsData['items'] ?? [];

        // Evaluasi visibilitas berbasis tampil_jika
        $visibleItems = [];
        foreach ($allItems as $item) {
            if (! ($item['aktif'] ?? true)) {
                continue;
            }

            $isVisible = true;
            if (isset($item['tampil_jika'])) {
                $cond = $item['tampil_jika'];
                $targetItem = $cond['item'] ?? '';
                $validValues = $cond['nilai_in'] ?? [];

                $parentAnswer = $answers->get($targetItem);
                if (! $parentAnswer || ! in_array($parentAnswer->nilai, $validValues, true)) {
                    $isVisible = false;
                }
            }

            $answered = $answers->get($item['kode']);

            $visibleItems[] = [
                'kode' => $item['kode'],
                'urutan' => $item['urutan'],
                'teks' => $item['teks'],
                'acuan' => $item['acuan'],
                'opsi' => $item['opsi'],
                'tampil_jika' => $item['tampil_jika'] ?? null,
                'is_visible' => $isVisible,
                'jawaban' => $answered ? [
                    'nilai' => $answered->nilai,
                    'nilai_num' => $answered->nilai_num,
                ] : null,
            ];
        }

        // Cari rekomendasi aktif jika sudah selesai
        $recommendation = null;
        if ($checkin->status === 'selesai') {
            $rec = $checkin->recommendations()->first();
            if ($rec) {
                $recommendation = [
                    'id' => $rec->id,
                    'tipe' => $rec->tipe,
                    'target_kode' => $rec->target_kode,
                    'alasan_item' => $rec->alasan_item,
                    'accepted' => $rec->accepted_at !== null,
                ];
            }
        }

        return response()->json([
            'tanggal' => $today,
            'status' => $checkin->status,
            'dilewati_count' => $checkin->dilewati_count,
            'items' => $visibleItems,
            'selesai' => $checkin->status === 'selesai',
            'rekomendasi' => $recommendation,
        ]);
    }

    /**
     * Autosave jawaban per butir kebiasaan.
     */
    public function jawab(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_kode' => ['required', 'string', 'max:50'],
            'nilai' => ['required', 'string', 'max:50'],
            'ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['message' => 'Belum masuk.'], 401);
        }

        if (! $this->checkPreTestCompleted($student)) {
            return response()->json(['message' => 'Pre-test belum selesai.'], 403);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $checkin = HabitCheckin::firstOrCreate(
            ['student_id' => $student->id, 'tanggal' => $today],
            ['status' => 'draft', 'started_at' => now()]
        );

        if ($checkin->status === 'selesai') {
            return response()->json([
                'message' => 'Jurnal hari ini sudah selesai dan tidak dapat diubah lagi.',
            ], 409);
        }

        // Cari opsi dan tanggapan di habits.json
        $habitsData = $this->getHabitsContent();
        $item = collect($habitsData['items'] ?? [])->firstWhere('kode', $validated['item_kode']);

        if (! $item) {
            return response()->json(['message' => 'Pertanyaan kebiasaan tidak ditemukan.'], 404);
        }

        $opsi = collect($item['opsi'] ?? [])->firstWhere('nilai', $validated['nilai']);
        if (! $opsi) {
            return response()->json(['message' => 'Opsi jawaban tidak valid.'], 422);
        }

        $nilaiNum = $opsi['num'] ?? null;
        $tanggapan = $opsi['tanggapan'] ?? 'Terima kasih sudah bercerita dengan jujur!';

        HabitAnswer::updateOrCreate(
            [
                'checkin_id' => $checkin->id,
                'item_kode' => $validated['item_kode'],
            ],
            [
                'nilai' => $validated['nilai'],
                'nilai_num' => $nilaiNum,
                'ms' => $validated['ms'] ?? null,
                'answered_at' => now(),
            ]
        );

        if ($checkin->status === 'dilewati') {
            $checkin->status = 'draft';
        }
        $checkin->started_at = $checkin->started_at ?? now();
        $checkin->save();

        return response()->json([
            'success' => true,
            'item_kode' => $validated['item_kode'],
            'nilai' => $validated['nilai'],
            'tanggapan' => $tanggapan,
        ]);
    }

    /**
     * Murid memilih "Nanti dulu" pada sapaan Dento hari ini.
     */
    public function lewati(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['message' => 'Belum masuk.'], 401);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $checkin = HabitCheckin::firstOrCreate(
            ['student_id' => $student->id, 'tanggal' => $today],
            ['status' => 'draft']
        );

        if ($checkin->status !== 'selesai') {
            $checkin->dilewati_count += 1;
            $checkin->status = 'dilewati';
            $checkin->save();
        }

        return response()->json([
            'success' => true,
            'dilewati_count' => $checkin->dilewati_count,
        ]);
    }

    /**
     * Menyelesaikan check-in jurnal hari ini, memberikan reward netral & menentukan rekomendasi.
     */
    public function selesai(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['message' => 'Belum masuk.'], 401);
        }

        if (! $this->checkPreTestCompleted($student)) {
            return response()->json(['message' => 'Pre-test belum selesai.'], 403);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $checkin = HabitCheckin::where('student_id', $student->id)
            ->where('tanggal', $today)
            ->first();

        if (! $checkin) {
            return response()->json(['message' => 'Check-in hari ini belum dimulai.'], 400);
        }

        if ($checkin->status === 'selesai') {
            return response()->json(['message' => 'Jurnal hari ini sudah selesai.'], 409);
        }

        $habitsData = $this->getHabitsContent();
        $items = $habitsData['items'] ?? [];
        $existingAnswers = $checkin->answers->keyBy('item_kode');

        // Pastikan semua pertanyaan yang memenuhi syarat telah dijawab
        foreach ($items as $item) {
            if (! ($item['aktif'] ?? true)) {
                continue;
            }

            $condMet = true;
            if (isset($item['tampil_jika'])) {
                $cond = $item['tampil_jika'];
                $parentAns = $existingAnswers->get($cond['item'] ?? '');
                if (! $parentAns || ! in_array($parentAns->nilai, $cond['nilai_in'] ?? [], true)) {
                    $condMet = false;
                }
            }

            if (! $condMet) {
                // Item dilewati karena syarat tampil_jika tidak terpenuhi
                HabitAnswer::updateOrCreate(
                    [
                        'checkin_id' => $checkin->id,
                        'item_kode' => $item['kode'],
                    ],
                    [
                        'nilai' => 'tidak_berlaku',
                        'nilai_num' => null,
                        'answered_at' => now(),
                    ]
                );
            } else {
                if (! $existingAnswers->has($item['kode'])) {
                    return response()->json([
                        'message' => 'Pertanyaan "'.$item['teks'].'" belum kamu jawab. Ceritakan semuanya dulu ya!',
                    ], 422);
                }
            }
        }

        $now = now();
        $durasi = $checkin->started_at ? max(1, $now->diffInSeconds($checkin->started_at)) : 30;

        $checkin->status = 'selesai';
        $checkin->finished_at = $now;
        $checkin->durasi_detik = $durasi;
        $checkin->save();

        // Reward jujur: selalu sama apa pun isinya (Koin 8, XP 10, Kebahagiaan +5)
        // Kebersihan Dento TIDAK disentuh sama sekali (isolasi status hewan)
        $rewardConfig = config('dento.habit.reward', ['coins' => 8, 'xp' => 10, 'happiness' => 5]);
        $koinReward = $rewardConfig['coins'] ?? 8;
        $xpReward = $rewardConfig['xp'] ?? 10;
        $happinessReward = $rewardConfig['happiness'] ?? 5;

        $pet = $student->petState;
        if ($pet) {
            $pet->increment('koin', $koinReward);
            $pet->addXp($xpReward);
            $pet->kebahagiaan = min(100, $pet->kebahagiaan + $happinessReward);
            $pet->save();
        }

        // Tentukan Rekomendasi Adaptif (Maksimal 1 per hari) dari habit_rules.json
        $recommendationData = $this->evaluateRecommendation($checkin, $student);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih banyak sudah bercerita tentang gigimu hari ini!',
            'reward' => [
                'koin' => $koinReward,
                'xp' => $xpReward,
                'kebahagiaan' => $happinessReward,
            ],
            'rekomendasi' => $recommendationData,
            'streak' => $student->school_days_streak,
        ]);
    }

    /**
     * Memilih 1 rekomendasi adaptif pertama yang cocok berdasarkan aturan prioritas.
     */
    private function evaluateRecommendation(HabitCheckin $checkin, $student): ?array
    {
        $rulesPath = database_path('content/habit_rules.json');
        if (! File::exists($rulesPath)) {
            return null;
        }

        $rulesData = json_decode(File::get($rulesPath), true);
        $rules = $rulesData['rules'] ?? [];
        usort($rules, fn ($a, $b) => ($a['prioritas'] ?? 99) <=> ($b['prioritas'] ?? 99));

        $answers = $checkin->answers->keyBy('item_kode');

        foreach ($rules as $rule) {
            $cond = $rule['jika'] ?? [];
            $itemKode = $cond['item'] ?? '';
            $ans = $answers->get($itemKode);

            if (! $ans) {
                continue;
            }

            $matched = false;
            if (isset($cond['nilai']) && $ans->nilai === $cond['nilai']) {
                $matched = true;
            } elseif (isset($cond['nilai_in']) && in_array($ans->nilai, $cond['nilai_in'], true)) {
                $matched = true;
            }

            if ($matched) {
                $recConfig = $rule['rekomendasi'] ?? [];
                $rec = HabitRecommendation::create([
                    'checkin_id' => $checkin->id,
                    'student_id' => $student->id,
                    'tipe' => $recConfig['tipe'] ?? 'video',
                    'target_kode' => $recConfig['target'] ?? '',
                    'alasan_item' => $itemKode,
                    'shown_at' => now(),
                ]);

                return [
                    'id' => $rec->id,
                    'tipe' => $rec->tipe,
                    'target_kode' => $rec->target_kode,
                    'judul' => $recConfig['judul'] ?? 'Rekomendasi Spesial Dento',
                    'deskripsi' => $recConfig['deskripsi'] ?? '',
                ];
            }
        }

        return null;
    }

    /**
     * Murid menerima rekomendasi adaptif.
     */
    public function terimaRekomendasi(Request $request, int $id): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $rec = HabitRecommendation::where('id', $id)->where('student_id', $student->id)->first();

        if (! $rec) {
            return response()->json(['message' => 'Rekomendasi tidak ditemukan.'], 404);
        }

        $rec->update(['accepted_at' => now()]);

        return response()->json([
            'success' => true,
            'target_kode' => $rec->target_kode,
            'tipe' => $rec->tipe,
        ]);
    }

    /**
     * Murid menolak / mengabaikan rekomendasi ("Nanti saja").
     */
    public function tolakRekomendasi(Request $request, int $id): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $rec = HabitRecommendation::where('id', $id)->where('student_id', $student->id)->first();

        if ($rec && ! $rec->accepted_at) {
            // Biarkan accepted_at tetap null
        }

        return response()->json(['success' => true]);
    }

    /**
     * Ringkasan Jurnal: kalender 14 hari lapor, rekap 7 hari diri sendiri, streak, & lencana.
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['message' => 'Belum masuk.'], 401);
        }

        $today = Carbon::today('Asia/Jakarta');

        // 14 hari kalender
        $checkins14 = HabitCheckin::where('student_id', $student->id)
            ->where('tanggal', '>=', $today->copy()->subDays(13)->toDateString())
            ->where('tanggal', '<=', $today->toDateString())
            ->get()
            ->keyBy(fn ($c) => $c->tanggal->toDateString());

        $kalender14 = [];
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($i = 13; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dateStr = $date->toDateString();
            $checkin = $checkins14->get($dateStr);

            $kalender14[] = [
                'tanggal' => $dateStr,
                'hari' => $hariIndo[$date->dayOfWeek],
                'is_today' => $i === 0,
                'sudah_melapor' => $checkin && $checkin->status === 'selesai',
            ];
        }

        // Agregat 7 hari terakhir (hanya menghitung hari yang murid laporkan)
        $sevenDaysAgo = $today->copy()->subDays(6)->toDateString();
        $checkinIds7 = HabitCheckin::where('student_id', $student->id)
            ->where('tanggal', '>=', $sevenDaysAgo)
            ->where('tanggal', '<=', $today->toDateString())
            ->where('status', 'selesai')
            ->pluck('id');

        $reportedDaysCount = $checkinIds7->count();

        $answers7 = HabitAnswer::whereIn('checkin_id', $checkinIds7)->get();
        $sikatPagiCount = $answers7->where('item_kode', 'sikat_pagi')->where('nilai', 'sudah')->count();
        $sikatMalamCount = $answers7->where('item_kode', 'sikat_malam')->where('nilai', 'sudah')->count();
        $kumurCount = $answers7->where('item_kode', 'kumur')->whereIn('nilai', ['ya', 'kadang'])->count();
        $airPutihCount = $answers7->where('item_kode', 'air_putih')->where('nilai', 'sudah')->count();

        $totalCompleted = HabitCheckin::where('student_id', $student->id)
            ->where('status', 'selesai')
            ->count();

        // Lencana Pencerita (3, 7, 14 check-in selesai)
        $lencana = [
            [
                'kode' => 'pencerita_1',
                'nama' => 'Pencerita Pemula',
                'syarat' => 3,
                'terbuka' => $totalCompleted >= 3,
            ],
            [
                'kode' => 'pencerita_2',
                'nama' => 'Pencerita Hebat',
                'syarat' => 7,
                'terbuka' => $totalCompleted >= 7,
            ],
            [
                'kode' => 'pencerita_3',
                'nama' => 'Pencerita Teladan',
                'syarat' => 14,
                'terbuka' => $totalCompleted >= 14,
            ],
        ];

        return response()->json([
            'kalender' => $kalender14,
            'hari_lapor_7_hari' => $reportedDaysCount,
            'rekap_7_hari' => [
                'sikat_pagi' => [
                    'label' => 'Sikat gigi pagi',
                    'count' => $sikatPagiCount,
                    'total' => $reportedDaysCount,
                ],
                'sikat_malam' => [
                    'label' => 'Sikat gigi sebelum tidur',
                    'count' => $sikatMalamCount,
                    'total' => $reportedDaysCount,
                ],
                'kumur' => [
                    'label' => 'Berkumur air putih setelah makan manis',
                    'count' => $kumurCount,
                    'total' => $reportedDaysCount,
                ],
                'air_putih' => [
                    'label' => 'Minum air putih',
                    'count' => $airPutihCount,
                    'total' => $reportedDaysCount,
                ],
            ],
            'streak' => $student->school_days_streak,
            'total_checkin' => $totalCompleted,
            'lencana' => $lencana,
        ]);
    }
}
