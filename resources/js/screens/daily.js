import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createDailyScreen(container, router) {
  let dailyData = null;
  let mascotInstance = null;

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 28px 16px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="background: #ffffff; padding: 36px 32px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); max-width: 520px; width: 100%; text-align: center; border: 2px solid #E8F6FB;">

        <div style="display: flex; justify-content: flex-end; margin-bottom: 8px;">
          <button id="btn-back-home" style="background: #F1F5F9; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; color: #475569; cursor: pointer;">
            &larr; Rumah
          </button>
        </div>

        <div id="mascot-daily-box" style="display: flex; justify-content: center; height: 180px; margin-bottom: 12px;"></div>

        <h1 style="color: var(--color-title); font-size: 26px; font-weight: 800; margin: 0 0 6px 0;">Pesan Hari Ini</h1>
        <p style="color: #64748B; font-size: 15px; margin: 0 0 24px 0;">Fakta & nasihat sehat dari Dento untukmu</p>

        <div id="daily-card" style="background: #FFFBEB; border: 2px dashed #FCD34D; border-radius: 18px; padding: 20px; margin-bottom: 28px; text-align: left; font-size: 17px; line-height: 1.5; color: #78350F; font-weight: 600;">
          Memuat pesan hari ini...
        </div>

        <div id="claim-area">
          <button id="btn-claim-daily" disabled
            style="min-height: 56px; width: 100%; background: #94A3B8; color: #fff; border: none; border-radius: 14px; font-size: 19px; font-weight: 700; cursor: not-allowed; transition: all 0.2s;">
            Klaim Hadiah 5 Koin 🪙
          </button>
        </div>

      </div>
    </div>
  `;

  const mascotBox = container.querySelector('#mascot-daily-box');
  mascotInstance = createMascot(mascotBox, {
    expression: 'senang',
    pose: 'tunjuk',
    kebersihan: 100,
  }, { size: 180, track: true });

  container.querySelector('#btn-back-home').addEventListener('click', () => {
    router.navigate('home');
  });

  try {
    dailyData = await api.get('/api/daily-message');
    const card = container.querySelector('#daily-card');
    card.textContent = `"${dailyData.pesan}"`;

    const claimBtn = container.querySelector('#btn-claim-daily');
    if (dailyData.is_claimed) {
      claimBtn.disabled = true;
      claimBtn.textContent = '✓ Koin Hari Ini Sudah Diklaim';
      claimBtn.style.background = '#E2E8F0';
      claimBtn.style.color = '#64748B';
    } else {
      claimBtn.disabled = false;
      claimBtn.style.background = '#059669';
      claimBtn.style.cursor = 'pointer';
      claimBtn.style.boxShadow = '0 4px 14px rgba(5, 150, 105, 0.3)';

      claimBtn.addEventListener('click', async () => {
        claimBtn.disabled = true;
        claimBtn.textContent = 'Mengklaim...';

        try {
          const res = await api.post('/api/daily-message/claim', {});
          sound.playEffect('pop');
          mascotInstance.set({ expression: 'semangat', pose: 'atas' });
          mascotInstance.react('jump');

          alert(res.message);
          claimBtn.textContent = '✓ Koin Berhasil Diklaim!';
          claimBtn.style.background = '#E2E8F0';
          claimBtn.style.color = '#64748B';
        } catch (err) {
          alert(err.message || 'Gagal mengklaim koin.');
          claimBtn.disabled = false;
          claimBtn.textContent = 'Klaim Hadiah 5 Koin 🪙';
        }
      });
    }
  } catch (e) {
    container.querySelector('#daily-card').textContent = 'Gagal memuat pesan harian.';
  }

  return {
    destroy() {
      if (mascotInstance) mascotInstance.destroy();
    },
  };
}
