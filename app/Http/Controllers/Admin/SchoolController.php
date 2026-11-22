<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', School::class);

        $schools = School::withCount(['classes', 'students'])->get();

        return view('admin.schools.index', compact('schools'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', School::class);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:10', 'unique:schools,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        School::create([
            'kode' => strtoupper(trim($validated['kode'])),
            'nama' => trim($validated['nama']),
        ]);

        return back()->with('success', 'Sekolah berhasil ditambahkan.');
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:10', 'unique:schools,kode,'.$school->id],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $school->update([
            'kode' => strtoupper(trim($validated['kode'])),
            'nama' => trim($validated['nama']),
        ]);

        return back()->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        $school->delete();

        return back()->with('success', 'Sekolah berhasil dihapus.');
    }
}
