<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoProgress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class VideoController extends Controller
{
    private function getVideosData(): array
    {
        $filePath = database_path('content/videos.json');
        if (! File::exists($filePath)) {
            return [];
        }

        return json_decode(File::get($filePath), true) ?? [];
    }

    /**
     * Mengambil daftar video dengan status terbuka bertahap dan progres murid.
     */
    public function index(): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $videos = $this->getVideosData();

        $progresses = VideoProgress::where('student_id', $student->id)
            ->get()
            ->keyBy('video_id');

        $preTestFinished = $student->testAttempts()
            ->where('jenis', 'pre')
            ->whereNotNull('finished_at')
            ->exists();

        $surveyFinished = $student->surveyResponses()->exists();

        $result = [];
        $previousCompleted = true; // Video inti 1 selalu terbuka

        foreach ($videos as $v) {
            $vid = $v['id'];
            $tipe = $v['tipe'] ?? 'inti';
            $prog = $progresses->get($vid);

            $isCompleted = $prog && $prog->selesai_at !== null;

            if ($tipe === 'inti') {
                $unlocked = $previousCompleted;
                $previousCompleted = $isCompleted;
            } elseif ($tipe === 'mikro' || $tipe === 'pembuka') {
                $unlocked = $preTestFinished;
            } elseif ($tipe === 'penutup') {
                $unlocked = $surveyFinished;
            } else {
                $unlocked = true;
            }

            $mediaFile = public_path('media/videos/'.($v['file'] ?? ''));
            $fileExists = File::exists($mediaFile);

            $result[] = [
                'id' => $vid,
                'urutan' => $v['urutan'] ?? 1,
                'tipe' => $tipe,
                'judul' => $v['judul'] ?? '',
                'deskripsi' => $v['deskripsi'] ?? '',
                'tp' => $v['tp'] ?? '',
                'durasi_detik' => $v['durasi_detik'] ?? 120,
                'file_name' => $v['file'] ?? '',
                'file_exists' => $fileExists,
                'file_url' => $fileExists ? asset('media/videos/'.$v['file']) : null,
                'poster_url' => isset($v['poster']) && File::exists(public_path('media/videos/'.$v['poster']))
                    ? asset('media/videos/'.$v['poster'])
                    : null,
                'unlocked' => $unlocked,
                'is_completed' => $isCompleted,
                'has_reward' => $prog && $prog->skor_tantangan !== null,
                'detik_ditonton' => $prog ? $prog->detik_ditonton : 0,
                'sumber' => $prog ? $prog->sumber : null,
                'tantangan' => $v['tantangan'] ?? [],
            ];
        }

        return response()->json([
            'videos' => $result,
        ]);
    }

    /**
     * Mencatat detik tonton yang sedang berjalan.
     */
    public function progress(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'detik' => ['required', 'integer', 'min:0'],
            'sumber' => ['nullable', 'string', 'max:50'],
        ]);

        $student = Auth::guard('student')->user();
        $videos = collect($this->getVideosData());
        $videoMeta = $videos->firstWhere('id', $id);

        if (! $videoMeta) {
            return response()->json(['message' => 'Video tidak ditemukan.'], 404);
        }

        $durasi = (int) ($videoMeta['durasi_detik'] ?? 120);
        $detik = min($durasi, (int) $validated['detik']);

        $prog = VideoProgress::firstOrCreate(
            ['student_id' => $student->id, 'video_id' => $id],
            ['detik_ditonton' => 0]
        );

        if ($detik > $prog->detik_ditonton) {
            $prog->detik_ditonton = $detik;
        }

        if (! empty($validated['sumber']) && ! $prog->sumber) {
            $prog->sumber = $validated['sumber'];
        }

        $prog->save();

        return response()->json([
            'success' => true,
            'detik_ditonton' => $prog->detik_ditonton,
        ]);
    }

    /**
     * Menandai video selesai (ditonton >= 90% atau override pendamping guru).
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $student = Auth::guard('student')->user();
        $videos = collect($this->getVideosData());
        $videoMeta = $videos->firstWhere('id', $id);

        if (! $videoMeta) {
            return response()->json(['message' => 'Video tidak ditemukan.'], 404);
        }

        $durasi = (int) ($videoMeta['durasi_detik'] ?? 120);
        $minRequired = (int) round($durasi * 0.9);

        $prog = VideoProgress::firstOrCreate(
            ['student_id' => $student->id, 'video_id' => $id],
            ['detik_ditonton' => 0]
        );

        $sumber = $request->input('sumber');
        if ($sumber && ! $prog->sumber) {
            $prog->sumber = $sumber;
        }

        $isTeacherAssisted = $request->boolean('teacher_assisted', false);

        // Validasi >= 90% durasi kecuali jika ditandai ditonton bersama guru
        if ($prog->detik_ditonton < $minRequired && ! $isTeacherAssisted) {
            return response()->json([
                'message' => 'Video belum ditonton hingga tuntas (minimal 90% durasi).',
                'detik_ditonton' => $prog->detik_ditonton,
                'min_required' => $minRequired,
            ], 422);
        }

        $awardedReward = null;
        if (! $prog->selesai_at) {
            $prog->selesai_at = Carbon::now();

            $tipe = $videoMeta['tipe'] ?? 'inti';
            // Video mikro langsung dapat koin 5 & xp 5 saat pertama kali tuntas
            if ($tipe === 'mikro' && $prog->skor_tantangan === null) {
                $prog->skor_tantangan = 100;
                $pet = $student->petState;
                if ($pet) {
                    $microReward = config('dento.habit.micro_video_reward', ['coins' => 5, 'xp' => 5]);
                    $pet->increment('koin', $microReward['coins'] ?? 5);
                    $pet->addXp($microReward['xp'] ?? 5);
                    $pet->save();
                    $awardedReward = $microReward;
                }
            }

            $prog->save();
        }

        return response()->json([
            'success' => true,
            'completed' => true,
            'selesai_at' => $prog->selesai_at->toIso8601String(),
            'reward' => $awardedReward,
        ]);
    }

    /**
     * Memproses jawaban kuis tantangan video dan memberikan hadiah sekali per video.
     */
    public function challenge(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $student = Auth::guard('student')->user();
        $videos = collect($this->getVideosData());
        $videoMeta = $videos->firstWhere('id', $id);

        if (! $videoMeta) {
            return response()->json(['message' => 'Video tidak ditemukan.'], 404);
        }

        $prog = VideoProgress::firstOrCreate(
            ['student_id' => $student->id, 'video_id' => $id],
            ['detik_ditonton' => 0]
        );

        $tantanganList = $videoMeta['tantangan'] ?? [];
        $correctCount = 0;

        foreach ($tantanganList as $index => $q) {
            $picked = $validated['answers'][$index] ?? null;
            if ($picked !== null && (int) $picked === (int) $q['benar']) {
                $correctCount++;
            }
        }

        $alreadyRewarded = ($prog->skor_tantangan !== null);
        $awardedCoins = 0;
        $awardedXp = 0;
        $cleanReward = 0;
        $happyReward = 0;

        if (! $alreadyRewarded) {
            // Hadiah SPEC A6 & A8: +5 kebersihan, +5 kebahagiaan, +15 koin, +20 XP
            $awardedCoins = 15;
            $awardedXp = (int) config('dento.xp.video', 20);
            $cleanReward = 5;
            $happyReward = 5;

            $pet = $student->petState;
            if ($pet) {
                $pet->kebersihan = min(100, $pet->kebersihan + $cleanReward);
                $pet->kebahagiaan = min(100, $pet->kebahagiaan + $happyReward);
                $pet->koin += $awardedCoins;
                $pet->addXp($awardedXp);
                $pet->save();
            }

            $prog->skor_tantangan = $correctCount;
            if (! $prog->selesai_at) {
                $prog->selesai_at = Carbon::now();
            }
            $prog->save();
        }

        return response()->json([
            'success' => true,
            'skor' => $correctCount,
            'total' => count($tantanganList),
            'reward_awarded' => ! $alreadyRewarded,
            'koin_didapat' => $awardedCoins,
            'xp_didapat' => $awardedXp,
            'kebersihan_tambah' => $cleanReward,
            'kebahagiaan_tambah' => $happyReward,
            'pet' => $student->petState,
        ]);
    }
}
