@extends('layouts.admin')

@section('title', 'Manajemen Sekolah - Dento')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="color: var(--color-title); margin: 0 0 6px 0; font-size: 24px;">Data Sekolah</h1>
        <p style="color: #64748B; margin: 0; font-size: 14px;">Kelola daftar sekolah mitra penelitian Pilmapres.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Form Tambah Sekolah -->
    <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; margin-bottom: 16px;">Tambah Sekolah</h3>
        <form method="POST" action="{{ route('admin.schools.store') }}">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Kode Sekolah (misal: S1, S2)</label>
                <input type="text" name="kode" required placeholder="S1" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Nama Sekolah</label>
                <input type="text" name="nama" required placeholder="SD Negeri 1 Magelang" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
            </div>
            <button type="submit" style="width: 100%; padding: 10px; background-color: var(--color-sky-dark); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Simpan Sekolah
            </button>
        </form>
    </div>

    <!-- Tabel Sekolah -->
    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
            <thead>
                <tr style="background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 12px 16px;">Kode</th>
                    <th style="padding: 12px 16px;">Nama Sekolah</th>
                    <th style="padding: 12px 16px;">Jumlah Kelas</th>
                    <th style="padding: 12px 16px;">Jumlah Murid</th>
                    <th style="padding: 12px 16px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 16px; font-weight: 700; color: var(--color-title);">{{ $school->kode }}</td>
                        <td style="padding: 12px 16px;">{{ $school->nama }}</td>
                        <td style="padding: 12px 16px;">{{ $school->classes_count }} kelas</td>
                        <td style="padding: 12px 16px;">{{ $school->students_count }} murid</td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" style="display:inline;" onsubmit="return confirm('Hapus sekolah {{ $school->nama }}? Semua kelas dan murid di sekolah ini akan ikut terhapus!');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none; border:none; color: #ef4444; cursor:pointer; font-size:13px; font-weight:600; text-decoration:underline;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: #64748B;">Belum ada data sekolah.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
