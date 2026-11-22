@extends('layouts.admin')

@section('title', 'Detail Peserta ' . $student->kode . ' - Dento')

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="{{ route('admin.students.index') }}" style="color: #64748B; text-decoration: none; font-size: 14px;">&larr; Kembali ke Daftar Murid</a>
        <h1 style="color: var(--color-title); margin: 8px 0 0 0; font-size: 26px;">Peserta: {{ $student->kode }}</h1>
        <p style="color: #64748B; margin: 4px 0 0 0; font-size: 15px;">
            {{ $student->school->nama }} &bull; {{ $student->schoolClass->nama }}
        </p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Kolom Kiri: Status Hewan & Akun -->
    <div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                Status Dento
            </h3>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px;">
                <div>Nama Hewan: <strong>{{ $student->pet_name ?? 'Belum dipilih' }}</strong></div>
                <div>Level: <strong>Tingkat {{ $student->petState->level ?? 1 }}</strong></div>
                <div>XP: <strong>{{ $student->petState->xp ?? 0 }} XP</strong></div>
                <div>Koin: <strong>{{ $student->petState->koin ?? 0 }} koin</strong></div>
                <div>Kebersihan: <strong>{{ $student->petState->kebersihan ?? 100 }}%</strong></div>
                <div>Kebahagiaan: <strong>{{ $student->petState->kebahagiaan ?? 100 }}%</strong></div>
                <div>Hari Aktif: <strong>{{ $student->active_days_count }} hari</strong></div>
                <div>Persetujuan Riset: <strong>{{ $student->consent_confirmed_at ? 'Disetujui (' . $student->consent_confirmed_at->format('d/m/Y H:i') . ')' : 'Belum' }}</strong></div>
            </div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                Riwayat Login
            </h3>
            <ul style="padding-left: 20px; margin: 0; font-size: 13px; color: #475569; max-height: 200px; overflow-y: auto;">
                @forelse($student->loginLogs()->latest('login_at')->take(10)->get() as $log)
                    <li style="margin-bottom: 6px;">{{ $log->login_at->translatedFormat('d M Y, H:i') }} WIB</li>
                @empty
                    <li>Belum ada riwayat masuk.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Kolom Kanan: Garis Waktu Tes, Video, & Game -->
    <div>
        <!-- Hasil Tes -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                Hasil Pre-Test & Post-Test
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                @php
                    $pre = $student->testAttempts->firstWhere('jenis', 'pre');
                    $post = $student->testAttempts->firstWhere('jenis', 'post');
                @endphp
                <div style="background: #F8FAFC; padding: 14px; border-radius: 8px; border-left: 4px solid #059669;">
                    <div style="font-weight: 700; color: #059669; margin-bottom: 6px;">Pre-Test</div>
                    @if($pre && $pre->finished_at)
                        <div style="font-size: 22px; font-weight: 800; color: #1E293B;">{{ $pre->skor }} / {{ $pre->total }}</div>
                        <div style="font-size: 12px; color: #64748B;">Selesai: {{ $pre->finished_at->format('d/m/Y H:i') }} ({{ $pre->durasi_detik }} detik)</div>
                    @else
                        <div style="color: #64748B; font-size: 13px;">{{ $pre ? 'Sedang dikerjakan' : 'Belum dimulai' }}</div>
                    @endif
                </div>

                <div style="background: #F8FAFC; padding: 14px; border-radius: 8px; border-left: 4px solid #2563EB;">
                    <div style="font-weight: 700; color: #2563EB; margin-bottom: 6px;">Post-Test</div>
                    @if($post && $post->finished_at)
                        <div style="font-size: 22px; font-weight: 800; color: #1E293B;">{{ $post->skor }} / {{ $post->total }}</div>
                        <div style="font-size: 12px; color: #64748B;">Selesai: {{ $post->finished_at->format('d/m/Y H:i') }} ({{ $post->durasi_detik }} detik)</div>
                    @else
                        <div style="color: #64748B; font-size: 13px;">{{ $post ? 'Sedang dikerjakan' : 'Terkunci / Belum dimulai' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Progres Video & Game -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                Aktivitas Video & Mini Game
            </h3>
            <div style="margin-bottom: 16px;">
                <strong style="font-size: 14px; color: #475569; display: block; margin-bottom: 8px;">Video Ditonton:</strong>
                <div style="font-size: 13px; color: #334155;">
                    {{ $student->videoProgress->whereNotNull('selesai_at')->count() }} dari 4 video telah diselesaikan.
                </div>
            </div>
            <div>
                <strong style="font-size: 14px; color: #475569; display: block; margin-bottom: 8px;">Sesi Mini Game Terakhir:</strong>
                <ul style="padding-left: 20px; margin: 0; font-size: 13px; color: #475569;">
                    @forelse($student->gameSessions()->latest('started_at')->take(5)->get() as $session)
                        <li style="margin-bottom: 4px;">
                            <strong>{{ $session->game_kode }}</strong>: Skor {{ $session->skor }} ({{ $session->started_at->format('d/m/Y H:i') }})
                        </li>
                    @empty
                        <li>Belum ada riwayat permainan.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
