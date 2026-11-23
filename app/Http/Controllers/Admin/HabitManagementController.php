<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HabitAnswer;
use App\Models\HabitCheckin;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HabitManagementController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('web')->user();

        // Scope sekolah
        $schools = $user->isPeneliti() ? School::orderBy('nama')->get() : collect();
        $schoolId = $user->isGuru() ? $user->school_id : $request->query('school_id');

        $classesQuery = SchoolClass::query();
        if ($schoolId) {
            $classesQuery->where('school_id', $schoolId);
        }
        $classes = $classesQuery->orderBy('nama')->get();
        $classId = $request->query('class_id');

        // Scope query murid
        $studentsQuery = Student::query();
        if ($schoolId) {
            $studentsQuery->where('school_id', $schoolId);
        }
        if ($classId) {
            $studentsQuery->where('class_id', $classId);
        }
        $studentIds = $studentsQuery->pluck('id');
        $totalStudents = $studentIds->count();

        $today = Carbon::today('Asia/Jakarta')->toDateString();

        // Checkin hari ini
        $todayCheckins = HabitCheckin::whereIn('student_id', $studentIds)
            ->where('tanggal', $today)
            ->get();

        $todayDoneCount = $todayCheckins->where('status', 'selesai')->count();
        $todaySkippedCount = $todayCheckins->where('status', 'dilewati')->count();

        // Total checkin selesai keseluruhan
        $allCheckins = HabitCheckin::whereIn('student_id', $studentIds)
            ->where('status', 'selesai')
            ->get();

        $totalCompletedCheckins = $allCheckins->count();
        $avgDurationSeconds = (int) ($allCheckins->avg('durasi_detik') ?? 0);

        // Agregasi jawaban kebiasaan
        $allCompletedIds = $allCheckins->pluck('id');
        $answers = HabitAnswer::whereIn('checkin_id', $allCompletedIds)->get();

        // 1. Sikat Pagi
        $sikatPagiTotal = $answers->where('item_kode', 'sikat_pagi')->count();
        $sikatPagiSudah = $answers->where('item_kode', 'sikat_pagi')->where('nilai', 'sudah')->count();
        $sikatPagiPct = $sikatPagiTotal > 0 ? round(($sikatPagiSudah / $sikatPagiTotal) * 100, 1) : 0;

        // 2. Sikat Malam
        $sikatMalamTotal = $answers->where('item_kode', 'sikat_malam')->count();
        $sikatMalamSudah = $answers->where('item_kode', 'sikat_malam')->where('nilai', 'sudah')->count();
        $sikatMalamPct = $sikatMalamTotal > 0 ? round(($sikatMalamSudah / $sikatMalamTotal) * 100, 1) : 0;

        // 3. Makanan Manis (distribusi 0, 1, 2, 3+)
        $manisAnswers = $answers->where('item_kode', 'manis');
        $manisTotal = $manisAnswers->count();
        $manis0 = $manisAnswers->where('nilai', '0')->count();
        $manis1 = $manisAnswers->where('nilai', '1')->count();
        $manis2 = $manisAnswers->where('nilai', '2')->count();
        $manis3 = $manisAnswers->where('nilai', '3_plus')->count();

        // 4. Kumur Air Putih
        $kumurAnswers = $answers->where('item_kode', 'kumur')->where('nilai', '!=', 'tidak_berlaku');
        $kumurTotal = $kumurAnswers->count();
        $kumurYa = $kumurAnswers->where('nilai', 'ya')->count();
        $kumurKadang = $kumurAnswers->where('nilai', 'kadang')->count();
        $kumurBelum = $kumurAnswers->where('nilai', 'belum')->count();

        // 5. Air Putih
        $airPutihTotal = $answers->where('item_kode', 'air_putih')->count();
        $airPutihSudah = $answers->where('item_kode', 'air_putih')->where('nilai', 'sudah')->count();
        $airPutihPct = $airPutihTotal > 0 ? round(($airPutihSudah / $airPutihTotal) * 100, 1) : 0;

        // Tren 7 hari terakhir untuk Chart.js
        $trendDates = [];
        $trendSikatMalam = [];
        $trendSikatPagi = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today('Asia/Jakarta')->subDays($i);
            $dateStr = $d->toDateString();
            $label = $d->translatedFormat('d M');
            $trendDates[] = $label;

            $dayCheckinIds = HabitCheckin::whereIn('student_id', $studentIds)
                ->where('tanggal', $dateStr)
                ->where('status', 'selesai')
                ->pluck('id');

            if ($dayCheckinIds->isEmpty()) {
                $trendSikatMalam[] = 0;
                $trendSikatPagi[] = 0;
            } else {
                $dayAns = HabitAnswer::whereIn('checkin_id', $dayCheckinIds)->get();
                $sm = $dayAns->where('item_kode', 'sikat_malam');
                $sp = $dayAns->where('item_kode', 'sikat_pagi');

                $trendSikatMalam[] = $sm->count() > 0 ? round(($sm->where('nilai', 'sudah')->count() / $sm->count()) * 100, 1) : 0;
                $trendSikatPagi[] = $sp->count() > 0 ? round(($sp->where('nilai', 'sudah')->count() / $sp->count()) * 100, 1) : 0;
            }
        }

        // Daftar murid dengan status jurnal
        $studentsList = Student::with(['schoolClass', 'habitCheckins' => function ($q) use ($today) {
            $q->where('tanggal', $today);
        }])
            ->whereIn('id', $studentIds)
            ->orderBy('kode')
            ->paginate(25);

        return view('admin.habits.index', compact(
            'today',
            'schools',
            'classes',
            'schoolId',
            'classId',
            'totalStudents',
            'todayDoneCount',
            'todaySkippedCount',
            'totalCompletedCheckins',
            'avgDurationSeconds',
            'sikatPagiPct',
            'sikatMalamPct',
            'airPutihPct',
            'manisTotal',
            'manis0',
            'manis1',
            'manis2',
            'manis3',
            'kumurTotal',
            'kumurYa',
            'kumurKadang',
            'kumurBelum',
            'trendDates',
            'trendSikatMalam',
            'trendSikatPagi',
            'studentsList'
        ));
    }
}
