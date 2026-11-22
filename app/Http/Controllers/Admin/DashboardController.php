<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\Student;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('web')->user();

        // Query students (mengikuti global scope sekolah untuk guru)
        $studentsQuery = Student::query();
        $totalStudents = $studentsQuery->count();
        $studentIds = $studentsQuery->pluck('id');

        $preCompleted = TestAttempt::whereIn('student_id', $studentIds)
            ->where('jenis', 'pre')
            ->whereNotNull('finished_at')
            ->count();

        $postCompleted = TestAttempt::whereIn('student_id', $studentIds)
            ->where('jenis', 'post')
            ->whereNotNull('finished_at')
            ->count();

        // Rata-rata hari aktif per murid
        $activeDaysSubquery = LoginLog::whereIn('student_id', $studentIds)
            ->select('student_id', DB::raw('COUNT(DISTINCT DATE(login_at)) as active_days'))
            ->groupBy('student_id');

        $avgActiveDays = $totalStudents > 0
            ? DB::table($activeDaysSubquery, 'sub')->avg('active_days') ?? 0
            : 0;

        return view('admin.dashboard', [
            'user' => $user,
            'totalStudents' => $totalStudents,
            'preCompleted' => $preCompleted,
            'postCompleted' => $postCompleted,
            'avgActiveDays' => round($avgActiveDays, 1),
        ]);
    }
}
