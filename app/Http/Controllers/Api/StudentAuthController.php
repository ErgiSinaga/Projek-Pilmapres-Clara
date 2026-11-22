<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\PetState;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class StudentAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $throttleKey = 'student_login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 15)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Terlalu banyak percobaan login. Silakan tunggu {$seconds} detik lagi.",
            ], 429);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'digits:'.config('dento.pin_length', 4)],
        ]);

        $kode = strtoupper(trim($validated['kode']));
        $pin = trim($validated['pin']);

        $student = Student::withoutGlobalScopes()->where('kode', $kode)->first();

        if (! $student) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Kode peserta tidak ditemukan. Periksa kembali kartu kode peserta dari gurumu ya.',
            ], 401);
        }

        if ($student->isLocked()) {
            return response()->json([
                'message' => 'Akunmu sedang terkunci sementara karena beberapa kali salah PIN. Minta bantuan guru ya.',
            ], 423);
        }

        if (config('dento.require_consent', false) && ! $student->consent_confirmed_at) {
            return response()->json([
                'message' => 'Akunmu belum aktif karena lembar persetujuan belum dicentang guru. Minta bantuan guru ya.',
            ], 403);
        }

        if (! Hash::check($pin, $student->pin_hash)) {
            RateLimiter::hit($throttleKey, 60);
            $student->increment('failed_attempts');
            $maxAttempts = config('dento.max_failed_attempts', 5);

            if ($student->failed_attempts >= $maxAttempts) {
                $lockMinutes = config('dento.lock_minutes', 5);
                $student->update([
                    'locked_until' => Carbon::now()->addMinutes($lockMinutes),
                    'failed_attempts' => 0,
                ]);

                return response()->json([
                    'message' => "PIN salah {$maxAttempts} kali. Akunmu terkunci sementara selama {$lockMinutes} menit. Minta bantuan guru ya.",
                ], 423);
            }

            $remaining = $maxAttempts - $student->failed_attempts;

            return response()->json([
                'message' => "PIN yang kamu masukkan belum tepat. Sisa percobaan: {$remaining} kali. Periksa kartu kodemu ya.",
            ], 401);
        }

        // Login berhasil
        RateLimiter::clear($throttleKey);
        $student->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        LoginLog::create([
            'student_id' => $student->id,
            'login_at' => Carbon::now(),
        ]);

        // Inisialisasi status hewan peliharaan jika belum ada
        if (! $student->petState()->exists()) {
            PetState::create([
                'student_id' => $student->id,
                'kebersihan' => 100,
                'kebahagiaan' => 100,
                'koin' => 0,
                'xp' => 0,
                'level' => 1,
                'last_seen_at' => Carbon::now(),
                'last_decay_at' => Carbon::now(),
            ]);
        }

        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Berhasil masuk! Selamat datang di Dento.',
            'student' => [
                'id' => $student->id,
                'kode' => $student->kode,
                'pet_name' => $student->pet_name,
                'needs_onboarding' => $student->pet_name === null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Kamu sudah berhasil keluar. Sampai jumpa lagi!',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $student = Auth::guard('student')->user();

        if (! $student) {
            return response()->json([
                'message' => 'Belum masuk.',
            ], 401);
        }

        $petState = $student->petState;
        $preTest = $student->testAttempts()->where('jenis', 'pre')->first();
        $postTest = $student->testAttempts()->where('jenis', 'post')->first();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'kode' => $student->kode,
                'pet_name' => $student->pet_name,
                'needs_onboarding' => $student->pet_name === null,
            ],
            'pet' => $petState,
            'pre_test_finished' => $preTest && $preTest->finished_at !== null,
            'post_test_finished' => $postTest && $postTest->finished_at !== null,
        ]);
    }
}
