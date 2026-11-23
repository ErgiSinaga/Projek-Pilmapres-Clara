import { api } from '../api.js';

export async function createJurnalRingkasanScreen(container, router) {
  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #E8F6FB;">
      <p style="font-size: 20px; color: var(--color-navy); font-weight: 700;">Memuat Ringkasan Jurnal...</p>
    </div>
  `;

  let ringkasan = null;
  try {
    ringkasan = await api.get('/api/jurnal/ringkasan');
  } catch (err) {
    container.innerHTML = `
      <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #E8F6FB; padding: 24px; text-align: center;">
        <h2 style="color: #EF4444; font-size: 22px; margin-bottom: 12px;">Terjadi Kendala</h2>
        <p style="color: #475569; font-size: 16px; margin-bottom: 20px;">${err.message || 'Gagal memuat ringkasan.'}</p>
        <button id="btn-back-home" style="padding: 12px 24px; background: var(--color-navy); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Kembali ke Beranda</button>
      </div>
    `;
    container.querySelector('#btn-back-home')?.addEventListener('click', () => router.navigate('home'));
    return {};
  }

  const kalender = ringkasan.kalender || [];
  const rekap = ringkasan.rekap_7_hari || {};
  const lencana = ringkasan.lencana || [];
  const streak = ringkasan.streak || 0;
  const reportedDays = ringkasan.hari_lapor_7_hari || 0;

  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; background: #F8FAFC;">
      
      <!-- Header -->
      <header style="padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; background: #FFFFFF; border-bottom: 2px solid #E2E8F0; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
        <button id="btn-back-home" style="padding: 8px 16px; background: #F1F5F9; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Kembali ke Rumah Dento
        </button>
        <h1 style="font-size: 18px; font-weight: 800; color: var(--color-navy); margin: 0;">
          Ringkasan Jurnal Gigi
        </h1>
        <button id="btn-isi-jurnal" style="padding: 8px 18px; background: #0D9488; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 800; cursor: pointer;">
          📝 Jurnal Hari Ini
        </button>
      </header>

      <!-- Main Content Container -->
      <main style="flex: 1; max-width: 800px; width: 100%; margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Banner Runtun Cerita (Streak) -->
        <div style="background: linear-gradient(135deg, #0D9488, #059669); border-radius: 24px; padding: 24px; color: #FFFFFF; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 8px 24px rgba(13,148,136,0.25);">
          <div>
            <div style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #CCFBF1; margin-bottom: 4px;">
              Runtun Lapor Hari Sekolah
            </div>
            <div style="font-size: 28px; font-weight: 800; line-height: 1.2;">
              ${streak > 0 ? `Kamu sudah cerita ${streak} hari berturut-turut!` : 'Ayo mulai bercerita hari ini!'}
            </div>
            <div style="font-size: 14px; color: #E6FFFA; margin-top: 6px;">
              Konsistensimu melapor adalah kunci kebiasaan sehat gigimu.
            </div>
          </div>
          <div style="width: 72px; height: 72px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><path d="M12 2c2 4 4 6 4 9a4 4 0 0 1-8 0c0-3 2-5 4-9z"/><path d="M12 11c1 2 2 3 2 4.5a2 2 0 0 1-4 0c0-1.5 1-2.5 2-4.5z"/></svg>
          </div>
        </div>

        <!-- Kalender 14 Hari Lapor -->
        <div style="background: #FFFFFF; border-radius: 24px; padding: 24px; border: 2px solid #E2E8F0; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 17px; font-weight: 800; color: var(--color-navy); margin: 0;">
              Kalender 2 Minggu Terakhir
            </h2>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #64748B;">
              <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #10B981;"></span>
              Hari sudah melapor
            </div>
          </div>

          <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px;">
            ${kalender.map(k => `
              <div style="background: ${k.sudah_melapor ? '#ECFDF5' : '#F8FAFC'}; border: 2px solid ${k.sudah_melapor ? '#10B981' : (k.is_today ? '#0D9488' : '#E2E8F0')}; border-radius: 14px; padding: 12px 6px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <span style="font-size: 11px; font-weight: 700; color: #64748B;">${k.hari.substring(0, 3)}</span>
                <span style="font-size: 15px; font-weight: 800; color: var(--color-navy);">${k.tanggal.split('-')[2]}</span>
                <div style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: ${k.sudah_melapor ? '#10B981' : 'transparent'};">
                  ${k.sudah_melapor ? `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  ` : `
                    <span style="color: #CBD5E1; font-size: 16px;">•</span>
                  `}
                </div>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Rekap 7 Hari Diri Sendiri (Bahasa Positif, Satu Warna Merek) -->
        <div style="background: #FFFFFF; border-radius: 24px; padding: 24px; border: 2px solid #E2E8F0; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
          <h2 style="font-size: 17px; font-weight: 800; color: var(--color-navy); margin: 0 0 6px 0;">
            Ceritamu dalam 7 Hari Terakhir
          </h2>
          <p style="font-size: 14px; color: #64748B; margin: 0 0 18px 0;">
            ${reportedDays > 0 ? `Berdasarkan ${reportedDays} hari yang kamu ceritakan:` : 'Belum ada catatan jurnal dalam 7 hari terakhir.'}
          </p>

          <div style="display: flex; flex-direction: column; gap: 14px;">
            ${Object.values(rekap).map(rk => `
              <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 14px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 15px; font-weight: 700; color: var(--color-navy);">
                  ${rk.label}
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #0D9488; background: #CCFBF1; padding: 6px 14px; border-radius: 20px;">
                  ${rk.count} dari ${rk.total} hari
                </div>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Lencana Pencerita -->
        <div style="background: #FFFFFF; border-radius: 24px; padding: 24px; border: 2px solid #E2E8F0; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
          <h2 style="font-size: 17px; font-weight: 800; color: var(--color-navy); margin: 0 0 16px 0;">
            Lencana Pencerita
          </h2>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            ${lencana.map(lc => `
              <div style="background: ${lc.terbuka ? '#F0FDF4' : '#F8FAFC'}; border: 2px solid ${lc.terbuka ? '#10B981' : '#E2E8F0'}; border-radius: 16px; padding: 18px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: ${lc.terbuka ? '#10B981' : '#E2E8F0'}; display: flex; align-items: center; justify-content: center; color: ${lc.terbuka ? '#fff' : '#94A3B8'};">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                </div>
                <span style="font-size: 15px; font-weight: 800; color: var(--color-navy);">${lc.nama}</span>
                <span style="font-size: 12px; font-weight: 600; color: ${lc.terbuka ? '#059669' : '#64748B'};">
                  ${lc.terbuka ? '✓ Terbuka' : `${lc.syarat} kali lapor`}
                </span>
              </div>
            `).join('')}
          </div>
        </div>

      </main>

      <footer style="padding: 16px 24px; text-align: center; color: #64748B; font-size: 13px;">
        Jurnal Gigi Dento • Teman Sehat Senyummu
      </footer>

    </div>
  `;

  container.querySelector('#btn-back-home')?.addEventListener('click', () => router.navigate('home'));
  container.querySelector('#btn-isi-jurnal')?.addEventListener('click', () => router.navigate('jurnal'));
  return {};
}
