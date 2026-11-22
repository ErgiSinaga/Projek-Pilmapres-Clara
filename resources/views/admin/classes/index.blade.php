@extends('layouts.admin')

@section('title', 'Manajemen Kelas - Dento')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="color: var(--color-title); margin: 0 0 6px 0; font-size: 24px;">Data Kelas</h1>
        <p style="color: #64748B; margin: 0; font-size: 14px;">Kelola rombongan belajar / kelas untuk penelitian.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Form Tambah Kelas -->
    <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; margin-bottom: 16px;">Tambah Kelas</h3>
        <form method="POST" action="{{ route('admin.classes.store') }}">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Pilih Sekolah</label>
                <select name="school_id" required style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                    @foreach($schools as $sc)
                        <option value="{{ $sc->id }}">{{ $sc->kode }} - {{ $sc->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Nama Kelas (misal: 3A, 4B)</label>
                <input type="text" name="nama" required placeholder="3A" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 4px;">Tingkat Kelas (SD)</label>
                <select name="tingkat" required style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                    <option value="3">Kelas 3</option>
                    <option value="4">Kelas 4</option>
                    <option value="5">Kelas 5</option>
                </select>
            </div>
            <button type="submit" style="width: 100%; padding: 10px; background-color: var(--color-sky-dark); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Simpan Kelas
            </button>
        </form>
    </div>

    <!-- Tabel Kelas -->
    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
            <thead>
                <tr style="background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 12px 16px;">Sekolah</th>
                    <th style="padding: 12px 16px;">Nama Kelas</th>
                    <th style="padding: 12px 16px;">Tingkat</th>
                    <th style="padding: 12px 16px;">Jumlah Murid</th>
                    <th style="padding: 12px 16px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $cl)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 16px; font-weight: 600;">{{ $cl->school->nama }} ({{ $cl->school->kode }})</td>
                        <td style="padding: 12px 16px; color: var(--color-title); font-weight: 700;">{{ $cl->nama }}</td>
                        <td style="padding: 12px 16px;">Kelas {{ $cl->tingkat }}</td>
                        <td style="padding: 12px 16px;">{{ $cl->students_count }} murid</td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <form method="POST" action="{{ route('admin.classes.destroy', $cl) }}" style="display:inline;" onsubmit="return confirm('Hapus kelas {{ $cl->nama }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none; border:none; color: #ef4444; cursor:pointer; font-size:13px; font-weight:600; text-decoration:underline;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: #64748B;">Belum ada data kelas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
