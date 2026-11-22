<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SchoolClass::class);

        $user = $request->user();
        $query = SchoolClass::with('school')->withCount('students');

        if ($user->isGuru()) {
            $query->where('school_id', $user->school_id);
        }

        $classes = $query->get();
        $schools = $user->isPeneliti() ? School::all() : School::where('id', $user->school_id)->get();

        return view('admin.classes.index', compact('classes', 'schools'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', SchoolClass::class);

        $validated = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'integer', 'between:1,6'],
        ]);

        SchoolClass::create($validated);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        Gate::authorize('update', $class);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'integer', 'between:1,6'],
        ]);

        $class->update($validated);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        Gate::authorize('delete', $class);

        $class->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }
}
