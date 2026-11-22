<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('web')->user();

        $selectedSchoolId = null;
        if ($user->isGuru()) {
            $selectedSchoolId = $user->school_id;
        } else {
            $selectedSchoolId = $request->input('school_id');
        }

        $selectedClassId = $request->input('class_id');

        $schools = $user->isPeneliti() ? School::all() : collect([$user->school]);
        $classes = SchoolClass::when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))->get();

        $studentsQuery = Student::query()
            ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
            ->when($selectedClassId, fn ($q) => $q->where('class_id', $selectedClassId))
            ->with(['school', 'schoolClass', 'testAttempts.answers.testItem']);

        $students = $studentsQuery->get();

        $pairedData = [];
        $totalPre = 0;
        $totalPost = 0;
        $totalDelta = 0;
        $totalNGain = 0;

        foreach ($students as $student) {
            $pre = $student->testAttempts->where('jenis', 'pre')->whereNotNull('finished_at')->first();
            $post = $student->testAttempts->where('jenis', 'post')->whereNotNull('finished_at')->first();

            $preScore = $pre ? $pre->skor : null;
            $postScore = $post ? $post->skor : null;
            $totalItems = $pre ? $pre->total : ($post ? $post->total : 10);

            $delta = null;
            $nGain = null;

            if ($preScore !== null && $postScore !== null) {
                $delta = $postScore - $preScore;

                if ($totalItems > $preScore) {
                    $nGain = round(($postScore - $preScore) / ($totalItems - $preScore), 2);
                } else {
                    $nGain = ($postScore >= $preScore) ? 1.0 : 0.0;
                }

                $totalPre += $preScore;
                $totalPost += $postScore;
                $totalDelta += $delta;
                $totalNGain += $nGain;

                $pairedData[] = [
                    'student' => $student,
                    'pre_score' => $preScore,
                    'post_score' => $postScore,
                    'delta' => $delta,
                    'n_gain' => $nGain,
                ];
            } else {
                $pairedData[] = [
                    'student' => $student,
                    'pre_score' => $preScore,
                    'post_score' => $postScore,
                    'delta' => null,
                    'n_gain' => null,
                ];
            }
        }

        $pairedCount = count(array_filter($pairedData, fn ($d) => $d['delta'] !== null));
        $meanPre = $pairedCount > 0 ? round($totalPre / $pairedCount, 2) : 0;
        $meanPost = $pairedCount > 0 ? round($totalPost / $pairedCount, 2) : 0;
        $meanDelta = $pairedCount > 0 ? round($totalDelta / $pairedCount, 2) : 0;
        $meanNGain = $pairedCount > 0 ? round($totalNGain / $pairedCount, 2) : 0;

        // Breakdown per TP (TP1 s/d TP5)
        $tps = ['TP1', 'TP2', 'TP3', 'TP4', 'TP5'];
        $tpLabels = ['TP1: Waktu & Durasi', 'TP2: Teknik Sikat', 'TP3: Pemilahan Makanan', 'TP4: Proses Karies', 'TP5: Peran Dokter Gigi'];
        $tpPrePercent = [];
        $tpPostPercent = [];

        foreach ($tps as $tp) {
            $preCorrect = 0;
            $preTotal = 0;
            $postCorrect = 0;
            $postTotal = 0;

            foreach ($students as $student) {
                $pre = $student->testAttempts->where('jenis', 'pre')->whereNotNull('finished_at')->first();
                if ($pre) {
                    foreach ($pre->answers as $ans) {
                        if ($ans->testItem && $ans->testItem->tp === $tp) {
                            $preTotal++;
                            if ($ans->benar) {
                                $preCorrect++;
                            }
                        }
                    }
                }

                $post = $student->testAttempts->where('jenis', 'post')->whereNotNull('finished_at')->first();
                if ($post) {
                    foreach ($post->answers as $ans) {
                        if ($ans->testItem && $ans->testItem->tp === $tp) {
                            $postTotal++;
                            if ($ans->benar) {
                                $postCorrect++;
                            }
                        }
                    }
                }
            }

            $tpPrePercent[] = $preTotal > 0 ? round(($preCorrect / $preTotal) * 100, 1) : 0;
            $tpPostPercent[] = $postTotal > 0 ? round(($postCorrect / $postTotal) * 100, 1) : 0;
        }

        return view('admin.results.index', compact(
            'user',
            'schools',
            'classes',
            'selectedSchoolId',
            'selectedClassId',
            'pairedData',
            'pairedCount',
            'meanPre',
            'meanPost',
            'meanDelta',
            'meanNGain',
            'tpLabels',
            'tpPrePercent',
            'tpPostPercent'
        ));
    }
}
