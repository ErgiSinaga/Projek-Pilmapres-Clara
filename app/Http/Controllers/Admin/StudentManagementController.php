<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PetState;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TestAttempt;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentManagementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $user = $request->user();
        $classId = $request->query('class_id');

        $classesQuery = SchoolClass::with('school');
        if ($user->isGuru()) {
            $classesQuery->where('school_id', $user->school_id);
        }
        $classes = $classesQuery->get();

        $studentsQuery = Student::with(['school', 'schoolClass', 'petState']);
        if ($classId) {
            $studentsQuery->where('class_id', $classId);
        }

        $students = $studentsQuery->orderBy('kode')->paginate(30);

        return view('admin.students.index', compact('students', 'classes', 'classId'));
    }

    public function generate(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $class = SchoolClass::with('school')->findOrFail($validated['class_id']);
        $user = $request->user();

        if ($user->isGuru() && (int) $user->school_id !== (int) $class->school_id) {
            abort(403, 'Anda tidak berwenang mengelola kelas di luar sekolah Anda.');
        }

        $school = $class->school;
        $count = (int) $validated['count'];
        $generatedCards = [];

        // Hitung nomor urut terakhir di sekolah ini untuk format {KODE_SEKOLAH}-{NN}
        $existingCodes = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->pluck('kode');

        $maxNumber = 0;
        foreach ($existingCodes as $c) {
            $parts = explode('-', $c);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $maxNumber = max($maxNumber, (int) $parts[1]);
            }
        }

        for ($i = 1; $i <= $count; $i++) {
            $seqNumber = $maxNumber + $i;
            $kode = sprintf('%s-%02d', $school->kode, $seqNumber);
            $plainPin = sprintf('%04d', random_int(0, 9999));

            $student = Student::create([
                'school_id' => $school->id,
                'class_id' => $class->id,
                'kode' => $kode,
                'pin_hash' => Hash::make($plainPin),
                'pet_name' => null,
                'failed_attempts' => 0,
                'locked_until' => null,
                'consent_confirmed_at' => null,
            ]);

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

            $generatedCards[] = [
                'kode' => $kode,
                'pin' => $plainPin,
                'kelas' => $class->nama,
                'sekolah' => $school->nama,
            ];
        }

        return redirect()->route('admin.students.cards')->with([
            'generated_cards' => $generatedCards,
            'success' => "Berhasil menghasilkan {$count} peserta didik baru.",
        ]);
    }

    public function printCards(): View|RedirectResponse
    {
        $cards = session('generated_cards');
        if (! $cards) {
            return redirect()->route('admin.students.index')->with('error', 'Tidak ada kartu kode yang baru dibuat untuk dicetak.');
        }

        return view('admin.students.cards', compact('cards'));
    }

    public function resetPin(Student $student): RedirectResponse
    {
        Gate::authorize('resetPin', $student);

        $newPin = sprintf('%04d', random_int(0, 9999));
        $student->update([
            'pin_hash' => Hash::make($newPin),
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        return back()->with('new_pin_alert', [
            'kode' => $student->kode,
            'pin' => $newPin,
        ]);
    }

    public function unlock(Student $student): RedirectResponse
    {
        Gate::authorize('unlock', $student);

        $student->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        return back()->with('success', "Akun peserta {$student->kode} berhasil dibuka kuncinya.");
    }

    public function toggleConsent(Student $student): RedirectResponse
    {
        Gate::authorize('markConsent', $student);

        $student->update([
            'consent_confirmed_at' => $student->consent_confirmed_at ? null : Carbon::now(),
        ]);

        $status = $student->consent_confirmed_at ? 'sudah ditandai' : 'dibatalkan';

        return back()->with('success', "Status persetujuan penelitian peserta {$student->kode} {$status}.");
    }

    public function unlockPostTest(Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        TestAttempt::firstOrCreate(
            [
                'student_id' => $student->id,
                'jenis' => 'post',
            ],
            [
                'started_at' => Carbon::now(),
                'skor' => 0,
                'total' => 10,
            ]
        );

        return back()->with('success', "Post-test untuk peserta {$student->kode} berhasil dibuka secara manual.");
    }

    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

        $student->load(['school', 'schoolClass', 'petState', 'testAttempts.answers', 'videoProgress', 'gameSessions', 'loginLogs']);

        return view('admin.students.show', compact('student'));
    }
}
