@extends('layouts.admin')

@section('title', 'Dashboard Panel Admin - Dento')

@section('content')
<div style="margin-bottom: 24px;">
    <h1 style="color: var(--color-title); margin: 0 0 8px 0; font-size: 26px;">Selamat Datang, {{ $user->name }}</h1>
    <p style="color: #64748B; margin: 0; font-size: 16px;">
        Peran: <strong>{{ ucfirst($user->role) }}</strong>
        @if($user->school)
            | Sekolah: <strong>{{ $user->school->nama }} ({{ $user->school->kode }})</strong>
        @endif
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px;">
    <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <div style="font-size: 14px; color: #64748B; margin-bottom: 8px;">Total Murid Terdaftar</div>
        <div style="font-size: 32px; font-weight: 700; color: var(--color-title);">{{ $totalStudents }}</div>
    </div>
    <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <div style="font-size: 14px; color: #64748B; margin-bottom: 8px;">Pre-Test Selesai</div>
        <div style="font-size: 32px; font-weight: 700; color: #059669;">{{ $preCompleted }}</div>
    </div>
    <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <div style="font-size: 14px; color: #64748B; margin-bottom: 8px;">Post-Test Selesai</div>
        <div style="font-size: 32px; font-weight: 700; color: #2563EB;">{{ $postCompleted }}</div>
    </div>
    <div style="background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <div style="font-size: 14px; color: #64748B; margin-bottom: 8px;">Rata-rata Hari Aktif</div>
        <div style="font-size: 32px; font-weight: 700; color: #D97706;">{{ $avgActiveDays }} hari</div>
    </div>
</div>

<div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
    <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; margin-bottom: 16px;">Aksi Cepat</h3>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('admin.students.index') }}"
            style="padding: 10px 18px; background-color: var(--color-sky-dark); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">
            Kelola Murid & Kartu PIN
        </a>
        @if($user->isPeneliti())
            <a href="{{ route('admin.schools.index') }}"
                style="padding: 10px 18px; background-color: #475569; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">
                Kelola Sekolah
            </a>
            <a href="{{ route('admin.classes.index') }}"
                style="padding: 10px 18px; background-color: #64748B; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">
                Kelola Kelas
            </a>
        @endif
    </div>
</div>
@endsection
