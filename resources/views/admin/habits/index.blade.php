@extends('layouts.admin')

@section('title', 'Analisis Kebiasaan Gigi - Dento Admin')

@section('content')
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="color: var(--color-title); margin: 0 0 8px 0; font-size: 26px;">Analisis Jurnal Kebiasaan Gigi</h1>
        <p style="color: #64748B; margin: 0; font-size: 15px;">
            Indikator perilaku kesehatan gigi anak (self-report) untuk melengkapi evaluasi pre/post-test penelitian Pilmapres.
        </p>
    </div>

    <!-- Tombol Unduh Ekspor CSV Kebiasaan -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <div class="dropdown" style="position: relative; display: inline-block;">
            <button id="btn-export-dropdown"
                style="padding: 10px 18px; background: #0D9488; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <span>📥 Unduh CSV Kebiasaan</span>
                <span style="font-size: 10px;">▼</span>
            </button>
            <div id="export-menu"
                style="display: none; position: absolute; right: 0; top: 100%; margin-top: 6px; background: #fff; border: 1px solid #CBD5E1; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 240px; z-index: 50; overflow: hidden;">
                <a href="{{ route('admin.export.kebiasaan-harian', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">1. Kebiasaan Harian Butir (CSV)</a>
                <a href="{{ route('admin.export.kebiasaan-ringkas', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #F1F5F9;">2. Kebiasaan Ringkas Murid (CSV)</a>
                <a href="{{ route('admin.export.rekomendasi-kebiasaan', request()->query()) }}" style="display: block; padding: 10px 14px; color: #1E293B; text-decoration: none; font-size: 13px; font-weight: 600;">3. Log Rekomendasi Adaptif (CSV)</a>
            </div>
        </div>
    </div>
</div>

<!-- Catatan Etika & Batasan Sistem (SPEC A1) -->
<div style="background: #F0FDF4; border-left: 4px solid #10B981; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; color: #065F46; line-height: 1.5;">
    <strong>ℹ️ Catatan Desain Penelitian:</strong> Seluruh data kebiasaan adalah <strong>laporan diri murid</strong> (self-report) melalui dialog check-in bersama Dento. Hadiah (koin & XP) diberikan setara apa pun jawabannya untuk memastikan kejujuran anak dan menjaga validitas data penelitian.
</div>

<!-- Filter Sekolah & Kelas -->
<div style="background: #ffffff; padding: 18px 22px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
    <form method="GET" action="{{ route('admin.habits.index') }}" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        @if(auth()->user()->isPeneliti() && $schools->isNotEmpty())
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 13px; font-weight: 700; color: #475569;">Sekolah:</label>
                <select name="school_id" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 14px; min-width: 180px;">
                    <option value="">Semua Sekolah</option>
                    @foreach($schools as $sc)
                        <option value="{{ $sc->id }}" {{ $schoolId == $sc->id ? 'selected' : '' }}>{{ $sc->nama }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 6px;">
            <label style="font-size: 13px; font-weight: 700; color: #475569;">Kelas:</label>
            <select name="class_id" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 14px; min-width: 160px;">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" style="padding: 8px 20px; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">
            Terapkan Filter
        </button>
    </form>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="color: #64748B; font-size: 13px; font-weight: 700; margin-bottom: 4px;">Partisipasi Hari Ini</div>
        <div style="font-size: 26px; font-weight: 800; color: #0D9488;">
            {{ $todayDoneCount }} / {{ $totalStudents }}
            <span style="font-size: 14px; font-weight: 600; color: #64748B;">({{ $totalStudents > 0 ? round(($todayDoneCount / $totalStudents) * 100) : 0 }}%)</span>
        </div>
        <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">{{ $todaySkippedCount }} murid memilih 'Nanti Dulu'</div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="color: #64748B; font-size: 13px; font-weight: 700; margin-bottom: 4px;">Total Laporan Selesai</div>
        <div style="font-size: 26px; font-weight: 800; color: var(--color-title);">
            {{ number_format($totalCompletedCheckins) }}
        </div>
        <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">Rata-rata durasi: {{ $avgDurationSeconds }} detik</div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="color: #64748B; font-size: 13px; font-weight: 700; margin-bottom: 4px;">Sikat Gigi Malam</div>
        <div style="font-size: 26px; font-weight: 800; color: #2563EB;">
            {{ $sikatMalamPct }}%
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Persentase jawaban 'Sudah'</div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="color: #64748B; font-size: 13px; font-weight: 700; margin-bottom: 4px;">Sikat Gigi Pagi</div>
        <div style="font-size: 26px; font-weight: 800; color: #16A34A;">
            {{ $sikatPagiPct }}%
        </div>
        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Persentase jawaban 'Sudah'</div>
    </div>
</div>

<!-- Grafik Tren Mingguan Sikat Malam vs Sikat Pagi -->
<div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
    <h3 style="margin: 0 0 16px 0; font-size: 17px; font-weight: 800; color: var(--color-navy);">
        Tren Kepatuhan Sikat Gigi 7 Hari Terakhir (% Sudah Sikat)
    </h3>
    <div style="height: 260px; position: relative;">
        <canvas id="trendChart"></canvas>
    </div>
</div>

<!-- Distribusi Jawaban per Butir Kebiasaan -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Makanan Manis -->
    <div style="background: #ffffff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <h4 style="margin: 0 0 14px 0; font-size: 15px; font-weight: 800; color: var(--color-navy);">
            Frekuensi Konsumsi Manis (24 Jam Terakhir)
        </h4>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between;">
                <span>0 kali (Tidak ada)</span>
                <strong>{{ $manis0 }} ({{ $manisTotal > 0 ? round(($manis0 / $manisTotal) * 100) : 0 }}%)</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>1 kali</span>
                <strong>{{ $manis1 }} ({{ $manisTotal > 0 ? round(($manis1 / $manisTotal) * 100) : 0 }}%)</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>2 kali</span>
                <strong>{{ $manis2 }} ({{ $manisTotal > 0 ? round(($manis2 / $manisTotal) * 100) : 0 }}%)</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>3 kali atau lebih</span>
                <strong>{{ $manis3 }} ({{ $manisTotal > 0 ? round(($manis3 / $manisTotal) * 100) : 0 }}%)</strong>
            </div>
        </div>
    </div>

    <!-- Berkumur Air Putih -->
    <div style="background: #ffffff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <h4 style="margin: 0 0 14px 0; font-size: 15px; font-weight: 800; color: var(--color-navy);">
            Berkumur Air Putih Setelah Makan Manis
        </h4>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Ya, selalu</span>
                <strong>{{ $kumurYa }} ({{ $kumurTotal > 0 ? round(($kumurYa / $kumurTotal) * 100) : 0 }}%)</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Kadang-kadang</span>
                <strong>{{ $kumurKadang }} ({{ $kumurTotal > 0 ? round(($kumurKadang / $kumurTotal) * 100) : 0 }}%)</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Belum berkumur</span>
                <strong>{{ $kumurBelum }} ({{ $kumurTotal > 0 ? round(($kumurBelum / $kumurTotal) * 100) : 0 }}%)</strong>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Status Jurnal Hari Ini & Tindakan Guru -->
<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--color-navy);">
            Status Jurnal Murid Hari Ini ({{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }})
        </h3>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #F8FAFC; border-bottom: 2px solid #E2E8F0; color: #475569;">
                    <th style="padding: 12px 18px;">Kode Peserta</th>
                    <th style="padding: 12px 18px;">Kelas</th>
                    <th style="padding: 12px 18px;">Status Hari Ini</th>
                    <th style="padding: 12px 18px;">Runtun (Streak)</th>
                    <th style="padding: 12px 18px; text-align: right;">Aksi Guru</th>
                </tr>
            </thead>
            <tbody>
                @forelse($studentsList as $std)
                    @php
                        $ch = $std->habitCheckins->first();
                        $status = $ch ? $ch->status : 'belum';
                    @endphp
                    <tr style="border-bottom: 1px solid #F1F5F9;">
                        <td style="padding: 12px 18px; font-weight: 700; color: var(--color-navy);">{{ $std->kode }}</td>
                        <td style="padding: 12px 18px; color: #64748B;">{{ $std->schoolClass?->nama ?? '-' }}</td>
                        <td style="padding: 12px 18px;">
                            @if($status === 'selesai')
                                <span style="background: #DCFCE7; color: #16A34A; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">✓ Selesai</span>
                            @elseif($status === 'draft')
                                <span style="background: #FEF3C7; color: #D97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">Draft (Sedang Isi)</span>
                            @elseif($status === 'dilewati')
                                <span style="background: #E2E8F0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">Dilewati (Nanti Dulu)</span>
                            @else
                                <span style="background: #F1F5F9; color: #94A3B8; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Belum Mengisi</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-weight: 700; color: #0D9488;">
                            {{ $std->school_days_streak }} hari
                        </td>
                        <td style="padding: 12px 18px; text-align: right;">
                            @if($status === 'selesai')
                                <form action="{{ route('admin.students.reopen-checkin', $std) }}" method="POST" style="display: inline;" onsubmit="return confirm('Buka kembali jurnal hari ini untuk murid ini agar bisa mengubah jawabannya?');">
                                    @csrf
                                    <button type="submit" style="padding: 6px 12px; background: #FEF3C7; color: #B45309; border: 1px solid #FCD34D; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                        🔓 Buka Kembali Jurnal
                                    </button>
                                </form>
                            @else
                                <span style="color: #94A3B8; font-size: 12px;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: #94A3B8;">Tidak ada data murid yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($studentsList->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid #E2E8F0;">
            {{ $studentsList->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dropdown ekspor CSV
    const exportBtn = document.getElementById('btn-export-dropdown');
    const exportMenu = document.getElementById('export-menu');
    if (exportBtn && exportMenu) {
        exportBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            exportMenu.style.display = exportMenu.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function () {
            exportMenu.style.display = 'none';
        });
    }

    // Chart.js Tren Mingguan
    const ctx = document.getElementById('trendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($trendDates) !!},
                datasets: [
                    {
                        label: 'Sikat Gigi Malam (%)',
                        data: {!! json_encode($trendSikatMalam) !!},
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Sikat Gigi Pagi (%)',
                        data: {!! json_encode($trendSikatPagi) !!},
                        borderColor: '#16A34A',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + '%'; }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
