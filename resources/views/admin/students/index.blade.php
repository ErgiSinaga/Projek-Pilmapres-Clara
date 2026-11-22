@extends('layouts.admin')

@section('title', 'Manajemen Murid - Dento')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="color: var(--color-title); margin: 0 0 6px 0; font-size: 24px;">Daftar Peserta Didik</h1>
        <p style="color: #64748B; margin: 0; font-size: 14px;">Identitas peserta menggunakan kode unik tanpa nama asli.</p>
    </div>

    <!-- Form Generate Massal -->
    <div style="background: #ffffff; padding: 16px; border-radius: 10px; border: 1px solid #cbd5e1;">
        <form method="POST" action="{{ route('admin.students.generate') }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            @csrf
            <strong style="font-size: 14px; color: var(--color-title);">Generate Murid:</strong>
            <select name="class_id" required style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px;">
                <option value="">-- Pilih Kelas --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->school->kode }} - {{ $c->nama }}</option>
                @endforeach
            </select>
            <input type="number" name="count" min="1" max="50" value="10" required style="width: 70px; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px;">
            <button type="submit" style="padding: 8px 16px; background-color: #059669; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">
                + Buat Kode & PIN
            </button>
        </form>
    </div>
</div>

@if(session('new_pin_alert'))
    <div style="background-color: #fef3c7; border: 2px solid #f59e0b; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
        <strong style="color: #92400e; font-size: 16px; display: block; margin-bottom: 4px;">⚠️ PIN Baru Dihasilkan:</strong>
        <p style="margin: 0; color: #78350f; font-size: 15px;">
            Kode Peserta: <strong>{{ session('new_pin_alert')['kode'] }}</strong> |
            PIN Baru: <strong style="font-size: 18px; color: #b45309; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px dashed #f59e0b;">{{ session('new_pin_alert')['pin'] }}</strong>
            <br><small>PIN ini hanya ditampilkan sekarang. Harap segera catat dan serahkan kepada murid!</small>
        </p>
    </div>
@endif

<!-- Filter Kelas -->
<div style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('admin.students.index') }}" style="display: flex; gap: 8px; align-items: center;">
        <span style="font-size: 14px; font-weight: 600;">Saring Kelas:</span>
        <select name="class_id" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px;">
            <option value="">Semua Kelas</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->school->kode }} - {{ $c->nama }}</option>
            @endforeach
        </select>
        @if($classId)
            <a href="{{ route('admin.students.index') }}" style="font-size: 13px; color: #ef4444; text-decoration: none; margin-left: 8px;">Reset Filter</a>
        @endif
    </form>
</div>

<!-- Tabel Murid -->
<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
        <thead>
            <tr style="background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 12px 16px;">Kode Peserta</th>
                <th style="padding: 12px 16px;">Sekolah / Kelas</th>
                <th style="padding: 12px 16px;">Nama Hewan</th>
                <th style="padding: 12px 16px;">Level & Koin</th>
                <th style="padding: 12px 16px;">Status Kunci</th>
                <th style="padding: 12px 16px;">Persetujuan (Consent)</th>
                <th style="padding: 12px 16px; text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $st)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px 16px; font-weight: 700; color: var(--color-title);">
                        {{ $st->kode }}
                    </td>
                    <td style="padding: 12px 16px;">
                        {{ $st->school->nama }}<br>
                        <small style="color: #64748B;">{{ $st->schoolClass->nama }}</small>
                    </td>
                    <td style="padding: 12px 16px;">
                        {{ $st->pet_name ?? '— (Belum onboarding)' }}
                    </td>
                    <td style="padding: 12px 16px;">
                        Lvl {{ $st->petState->level ?? 1 }} | {{ $st->petState->koin ?? 0 }} koin
                    </td>
                    <td style="padding: 12px 16px;">
                        @if($st->isLocked())
                            <span style="color: #ef4444; font-weight: 600;">🔒 Terkunci ({{ $st->locked_until->diffForHumans() }})</span>
                        @else
                            <span style="color: #059669;">Aktif</span>
                        @endif
                    </td>
                    <td style="padding: 12px 16px;">
                        <form method="POST" action="{{ route('admin.students.consent', $st) }}" style="display:inline;">
                            @csrf
                            <button type="submit" style="background:none; border:none; cursor:pointer; font-size:13px; text-decoration:underline; color: {{ $st->consent_confirmed_at ? '#059669' : '#dc2626' }};">
                                {{ $st->consent_confirmed_at ? '✓ Disetujui' : '✗ Belum Disetujui' }}
                            </button>
                        </form>
                    </td>
                    <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                        <a href="{{ route('admin.students.show', $st) }}" style="color: var(--color-sky-dark); text-decoration: none; margin-right: 8px; font-weight: 600;">Detail</a>

                        <form method="POST" action="{{ route('admin.students.reset-pin', $st) }}" style="display:inline;" onsubmit="return confirm('Buat PIN baru untuk {{ $st->kode }}? PIN lama tidak akan berlaku lagi.');">
                            @csrf
                            <button type="submit" style="background:none; border:none; color: #d97706; cursor:pointer; font-size:13px; font-weight:600; text-decoration:underline;">Reset PIN</button>
                        </form>

                        @if($st->isLocked())
                            <form method="POST" action="{{ route('admin.students.unlock', $st) }}" style="display:inline; margin-left: 8px;">
                                @csrf
                                <button type="submit" style="background:none; border:none; color: #059669; cursor:pointer; font-size:13px; font-weight:600; text-decoration:underline;">Buka Kunci</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 24px; text-align: center; color: #64748B;">
                        Belum ada peserta didik terdaftar. Gunakan formulir generate di atas untuk membuat kode peserta.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $students->links() }}
</div>
@endsection
