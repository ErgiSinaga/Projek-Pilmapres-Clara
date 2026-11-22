@extends('layouts.admin')

@section('title', 'Hasil Pre vs Post-Test - Dento')

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="color: var(--color-title); margin: 0 0 8px 0; font-size: 26px;">Hasil Evaluasi Pre-Test vs Post-Test</h1>
        <p style="color: #64748B; margin: 0; font-size: 15px;">
            Data evaluasi peningkatan pengetahuan kesehatan gigi murid untuk analisis penelitian Pilmapres.
        </p>
    </div>

    <!-- Tombol Ekspor CSV -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <div class="dropdown" style="position: relative; display: inline-block;">
            <button id="btn-export-dropdown"
                style="padding: 10px 18px; background: #059669; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <span>📥 Unduh Ekspor CSV</span>
                <span style="font-size: 10px;">▼</span>
            </button>
            <div id="export-menu"
                style="display: none; position: absolute; right: 0; top: 100%; margin-top: 6px; background: #fff; border: 1px solid #CBD5E1; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 220px; z-index: 50; overflow: hidden;">
                <a href="{{ route('admin.export.hasil-tes', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">1. Hasil Tes (Pre vs Post)</a>
                <a href="{{ route('admin.export.jawaban-butir', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">2. Jawaban Butir & Waktu</a>
                <a href="{{ route('admin.export.aktivitas', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">3. Sesi Aktivitas Mini Game</a>
                <a href="{{ route('admin.export.pilihan-makanan', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">4. Log Pilihan Makanan</a>
                <a href="{{ route('admin.export.survei', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">5. Kuesioner Kepuasan</a>
                <a href="{{ route('admin.export.login-hari-aktif', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600;">6. Hari Aktif & Frekuensi</a>
            </div>
        </div>
    </div>
</div>

<!-- Catatan Eksplisit Peneliti -->
<div style="background: #EFF6FF; border-left: 4px solid #3B82F6; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; color: #1E40AF; line-height: 1.5;">
    <strong>💡 Catatan Penelitian Pilmapres:</strong> Uji signifikansi statistik (paired sample t-test / Wilcoxon signed-rank test) dilakukan di luar sistem menggunakan software analisis (SPSS, JASP, atau R) dengan mengunduh data mentah melalui tombol <em>Unduh Ekspor CSV</em>.
</div>

<!-- Filter Sekolah & Kelas -->
<div style="background: #ffffff; padding: 18px 22px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
    <form method="GET" action="{{ route('admin.results.index') }}" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        @if($user->isPeneliti())
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 13px; font-weight: 700; color: #475569;">Sekolah:</label>
                <select name="school_id" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 14px; min-width: 180px;">
                    <option value="">Semua Sekolah</option>
                    @foreach($schools as $sc)
                        <option value="{{ $sc->id }}" {{ $selectedSchoolId == $sc->id ? 'selected' : '' }}>{{ $sc->nama }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 6px;">
            <label style="font-size: 13px; font-weight: 700; color: #475569;">Kelas:</label>
            <select name="class_id" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 14px; min-width: 160px;">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" style="padding: 9px 18px; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer;">
                Terapkan Filter
            </button>
            <a href="{{ route('admin.results.index') }}" style="padding: 9px 14px; background: #F1F5F9; color: #475569; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600; margin-left: 6px;">Reset</a>
        </div>
    </form>
</div>

<!-- Kartu Ringkasan Metrik (Pre, Post, Delta, N-Gain) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
    <div style="background: #ffffff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 13px; color: #64748B; margin-bottom: 6px;">Pasangan Lengkap (N)</div>
        <div style="font-size: 28px; font-weight: 800; color: var(--color-navy);">{{ $pairedCount }}</div>
    </div>
    <div style="background: #ffffff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 13px; color: #64748B; margin-bottom: 6px;">Rata-rata Pre-Test</div>
        <div style="font-size: 28px; font-weight: 800; color: #EF4444;">{{ $meanPre }} / 10</div>
    </div>
    <div style="background: #ffffff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 13px; color: #64748B; margin-bottom: 6px;">Rata-rata Post-Test</div>
        <div style="font-size: 28px; font-weight: 800; color: #2563EB;">{{ $meanPost }} / 10</div>
    </div>
    <div style="background: #ffffff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 13px; color: #64748B; margin-bottom: 6px;">Rata-rata Kenaikan (&Delta;)</div>
        <div style="font-size: 28px; font-weight: 800; color: #10B981;">+{{ $meanDelta }}</div>
    </div>
    <div style="background: #ffffff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 13px; color: #64748B; margin-bottom: 6px;">Rata-rata N-Gain</div>
        <div style="font-size: 28px; font-weight: 800; color: #7C3AED;">{{ $meanNGain }}</div>
    </div>
</div>

<!-- Grafik Batang Chart.js Pre vs Post per Tujuan Pembelajaran (TP) -->
<div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 28px;">
    <h3 style="margin-top: 0; color: var(--color-title); font-size: 18px; margin-bottom: 16px;">
        Perbandingan Tingkat Pemahaman per Tujuan Pembelajaran (TP)
    </h3>
    <div style="position: relative; height: 320px; width: 100%;">
        <canvas id="resultsChart"></canvas>
    </div>
</div>

<!-- Tabel Rincian per Murid -->
<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0;">
        <h3 style="margin: 0; color: var(--color-title); font-size: 18px;">Daftar Pasangan Pre-Test & Post-Test Murid</h3>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; color: #475569;">
                    <th style="padding: 12px 16px;">No</th>
                    <th style="padding: 12px 16px;">Kode Peserta</th>
                    <th style="padding: 12px 16px;">Kelas</th>
                    <th style="padding: 12px 16px;">Pre-Test</th>
                    <th style="padding: 12px 16px;">Post-Test</th>
                    <th style="padding: 12px 16px;">Selisih (&Delta;)</th>
                    <th style="padding: 12px 16px;">N-Gain</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pairedData as $index => $item)
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 12px 16px; color: #64748B;">{{ $index + 1 }}</td>
                        <td style="padding: 12px 16px; font-weight: 700; color: var(--color-title);">{{ $item['student']->kode }}</td>
                        <td style="padding: 12px 16px;">{{ $item['student']->schoolClass?->nama ?? '-' }}</td>
                        <td style="padding: 12px 16px; font-weight: 600; color: #EF4444;">
                            {{ $item['pre_score'] !== null ? $item['pre_score'] . ' / 10' : '-' }}
                        </td>
                        <td style="padding: 12px 16px; font-weight: 600; color: #2563EB;">
                            {{ $item['post_score'] !== null ? $item['post_score'] . ' / 10' : '-' }}
                        </td>
                        <td style="padding: 12px 16px; font-weight: 700; color: {{ ($item['delta'] ?? 0) >= 0 ? '#10B981' : '#DC2626' }};">
                            {{ $item['delta'] !== null ? (($item['delta'] >= 0 ? '+' : '') . $item['delta']) : '-' }}
                        </td>
                        <td style="padding: 12px 16px; font-weight: 700; color: #7C3AED;">
                            {{ $item['n_gain'] !== null ? $item['n_gain'] : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; color: #64748B;">
                            Belum ada data murid untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
@vite(['resources/js/admin-chart.js'])
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Dropdown Export Menu
        const btn = document.getElementById('btn-export-dropdown');
        const menu = document.getElementById('export-menu');
        if (btn && menu) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
            });
            document.addEventListener('click', function () {
                menu.style.display = 'none';
            });
        }

        // Render Chart.js
        const labels = @json($tpLabels);
        const preData = @json($tpPrePercent);
        const postData = @json($tpPostPercent);

        if (window.initResultsChart) {
            window.initResultsChart('resultsChart', labels, preData, postData);
        } else {
            // Jika Vite hot-load
            setTimeout(() => {
                if (window.initResultsChart) {
                    window.initResultsChart('resultsChart', labels, preData, postData);
                }
            }, 300);
        }
    });
</script>
@endpush
@endsection
