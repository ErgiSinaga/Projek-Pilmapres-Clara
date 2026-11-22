<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameEvent;
use App\Models\GameSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class GameController extends Controller
{
    public static array $validGames = [
        'pilah-makanan',
        'urutan-sikat',
        'sikat-bersih',
        'memory-fakta',
        'kuis-kilat',
    ];

    /**
     * Memulai sesi permainan dan mengirimkan konten yang diacak.
     */
    public function start(Request $request, string $kode): JsonResponse
    {
        if (! in_array($kode, self::$validGames, true)) {
            return response()->json(['message' => 'Kode permainan tidak ditemukan.'], 404);
        }

        $student = Auth::guard('student')->user();

        $session = GameSession::create([
            'student_id' => $student->id,
            'game_kode' => $kode,
            'started_at' => Carbon::now(),
            'skor' => 0,
        ]);

        $payload = [
            'session_id' => $session->id,
            'game_kode' => $kode,
        ];

        if ($kode === 'pilah-makanan') {
            $foodsFile = database_path('content/foods.json');
            $allFoods = File::exists($foodsFile) ? (json_decode(File::get($foodsFile), true) ?? []) : [];
            // Ambil 10 item acak
            shuffle($allFoods);
            $payload['items'] = array_slice($allFoods, 0, 10);
        } elseif ($kode === 'urutan-sikat') {
            $stepsFile = database_path('content/steps.json');
            $allSteps = File::exists($stepsFile) ? (json_decode(File::get($stepsFile), true) ?? []) : [];
            $shuffled = $allSteps;
            shuffle($shuffled);
            $payload['steps'] = $shuffled;
        } elseif ($kode === 'sikat-bersih') {
            $payload['zones'] = [
                ['id' => 'depan_atas', 'nama' => 'Depan Atas', 'target_strokes' => 12],
                ['id' => 'depan_bawah', 'nama' => 'Depan Bawah', 'target_strokes' => 12],
                ['id' => 'kiri_atas', 'nama' => 'Geraham Kiri Atas', 'target_strokes' => 12],
                ['id' => 'kanan_atas', 'nama' => 'Geraham Kanan Atas', 'target_strokes' => 12],
                ['id' => 'kiri_bawah', 'nama' => 'Geraham Kiri Bawah', 'target_strokes' => 12],
                ['id' => 'kanan_bawah', 'nama' => 'Geraham Kanan Bawah', 'target_strokes' => 12],
            ];
            $payload['timer_seconds'] = 120; // 2 menit cincin timer positif
        } elseif ($kode === 'memory-fakta') {
            // 6 pasang kartu konsep (gambar + kata)
            $conceptPairs = [
                ['key' => 'sikat', 'nama' => 'Sikat Gigi', 'icon' => '🪥', 'fakta' => 'Sikat 2x sehari'],
                ['key' => 'pasta', 'nama' => 'Pasta Fluorida', 'icon' => '🧴', 'fakta' => 'Biji kacang polong'],
                ['key' => 'apel', 'nama' => 'Apel Segar', 'icon' => '🍎', 'fakta' => 'Serat pembersih'],
                ['key' => 'air', 'nama' => 'Air Putih', 'icon' => '💧', 'fakta' => 'Bilas sisa kuman'],
                ['key' => 'dokter', 'nama' => 'Dokter Gigi', 'icon' => '🩺', 'fakta' => 'Periksa tiap 6 bulan'],
                ['key' => 'keju', 'nama' => 'Keju Kalsium', 'icon' => '🧀', 'fakta' => 'Kalsium kuat'],
            ];
            $cards = [];
            foreach ($conceptPairs as $pair) {
                $cards[] = ['id' => $pair['key'].'_a', 'pair_id' => $pair['key'], 'text' => $pair['nama'], 'icon' => $pair['icon']];
                $cards[] = ['id' => $pair['key'].'_b', 'pair_id' => $pair['key'], 'text' => $pair['fakta'], 'icon' => $pair['icon']];
            }
            shuffle($cards);
            $payload['cards'] = $cards;
        } elseif ($kode === 'kuis-kilat') {
            $quizFile = database_path('content/quiz_bank.json');
            $allQuiz = File::exists($quizFile) ? (json_decode(File::get($quizFile), true) ?? []) : [];
            shuffle($allQuiz);
            $selected = array_slice($allQuiz, 0, 5);
            $payload['questions'] = $selected;
        }

        return response()->json($payload);
    }

    /**
     * Mencatat kejadian penting dalam game (food_choice, dll.) ke game_events.
     */
    public function event(Request $request, string $kode): JsonResponse
    {
        $validated = $request->validate([
            'game_session_id' => ['required', 'exists:game_sessions,id'],
            'tipe' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        $student = Auth::guard('student')->user();

        GameEvent::create([
            'game_session_id' => $validated['game_session_id'],
            'student_id' => $student->id,
            'tipe' => $validated['tipe'],
            'payload' => $validated['payload'],
            'created_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Menyelesaikan sesi permainan, menghitung skor ter-clamp & hadiah server-side.
     */
    public function finish(Request $request, string $kode): JsonResponse
    {
        if (! in_array($kode, self::$validGames, true)) {
            return response()->json(['message' => 'Kode permainan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'game_session_id' => ['required', 'exists:game_sessions,id'],
            'skor' => ['required', 'numeric', 'min:0'],
            'detail' => ['nullable', 'array'],
        ]);

        $student = Auth::guard('student')->user();
        $session = GameSession::where('id', $validated['game_session_id'])
            ->where('student_id', $student->id)
            ->firstOrFail();

        $rawScore = (float) $validated['skor'];
        $cleanReward = 0;
        $happyReward = 0;
        $potentialCoins = 0;
        $clampedScore = 0;
        $xpReward = (int) config('dento.xp.activity', 10);

        // Perhitungan reward & clamp skor sesuai Tabel SPEC A6
        if ($kode === 'pilah-makanan') {
            $clampedScore = (int) min(10, max(0, round($rawScore)));
            $cleanReward = 0;
            $happyReward = 10;
            $potentialCoins = $clampedScore * 2; // 2 per benar, maks 20
        } elseif ($kode === 'urutan-sikat') {
            $clampedScore = (int) min(12, max(0, round($rawScore)));
            $cleanReward = 10;
            $happyReward = 0;
            $potentialCoins = $clampedScore; // maks 12
        } elseif ($kode === 'sikat-bersih') {
            $clampedScore = (int) min(100, max(0, round($rawScore)));
            $cleanReward = 30;
            $happyReward = 0;
            $potentialCoins = (int) min(20, round(($clampedScore / 100.0) * 20)); // maks 20
        } elseif ($kode === 'memory-fakta') {
            $clampedScore = (int) min(12, max(0, round($rawScore)));
            $cleanReward = 0;
            $happyReward = 10;
            $potentialCoins = $clampedScore; // maks 12
        } elseif ($kode === 'kuis-kilat') {
            $clampedScore = (int) min(5, max(0, round($rawScore)));
            $cleanReward = 0;
            $happyReward = 10;
            $potentialCoins = $clampedScore * 2; // 2 per benar, maks 10
        }

        // Batas koin per aktivitas per hari (SPEC A6 & config dento.php)
        $coinCap = (int) config('dento.coin_cap_per_activity_per_day', 30);
        $todayStart = Carbon::today()->startOfDay();

        $coinsAwardedToday = (int) GameSession::where('student_id', $student->id)
            ->where('game_kode', $kode)
            ->where('started_at', '>=', $todayStart)
            ->whereNotNull('finished_at')
            ->sum('skor'); // Menggunakan akumulasi koin yang tercatat di detail atau sesi

        $remainingCap = max(0, $coinCap - $coinsAwardedToday);
        $awardedCoins = min($remainingCap, $potentialCoins);

        // Update status hewan peliharaan
        $pet = $student->petState;
        if ($pet) {
            $pet->kebersihan = min(100, $pet->kebersihan + $cleanReward);
            $pet->kebahagiaan = min(100, $pet->kebahagiaan + $happyReward);
            $pet->koin += $awardedCoins;
            $pet->addXp($xpReward);
            $pet->save();
        }

        $session->update([
            'finished_at' => Carbon::now(),
            'skor' => $clampedScore,
            'detail' => array_merge($validated['detail'] ?? [], [
                'potential_coins' => $potentialCoins,
                'awarded_coins' => $awardedCoins,
                'coin_cap_reached' => ($awardedCoins < $potentialCoins),
            ]),
        ]);

        return response()->json([
            'message' => 'Hore! Aktivitas selesai dengan luar biasa!',
            'skor' => $clampedScore,
            'koin_didapat' => $awardedCoins,
            'xp_didapat' => $xpReward,
            'kebersihan_tambah' => $cleanReward,
            'kebahagiaan_tambah' => $happyReward,
            'pet' => $pet,
        ]);
    }
}
