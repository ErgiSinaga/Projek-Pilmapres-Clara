import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createSikatBersihGame(container, router) {
  let sessionData = null;
  let mascotInstance = null;
  let timerInterval = null;
  let elapsedSeconds = 0;
  const totalDuration = 120; // 2 menit timer positif

  const zones = [
    { id: 'kanan_atas', label: 'Geraham Kanan Atas', x: 40, y: 50, w: 100, h: 70, progress: 0, timeSpent: 0 },
    { id: 'depan_atas', label: 'Gigi Seri Atas', x: 150, y: 35, w: 120, h: 70, progress: 0, timeSpent: 0 },
    { id: 'kiri_atas', label: 'Geraham Kiri Atas', x: 280, y: 50, w: 100, h: 70, progress: 0, timeSpent: 0 },
    { id: 'kanan_bawah', label: 'Geraham Kanan Bawah', x: 40, y: 180, w: 100, h: 70, progress: 0, timeSpent: 0 },
    { id: 'depan_bawah', label: 'Gigi Seri Bawah', x: 150, y: 195, w: 120, h: 70, progress: 0, timeSpent: 0 },
    { id: 'kiri_bawah', label: 'Geraham Kiri Bawah', x: 280, y: 180, w: 100, h: 70, progress: 0, timeSpent: 0 },
  ];

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 24px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="max-width: 760px; width: 100%; background: #ffffff; padding: 32px 24px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); border: 2px solid #E8F6FB; text-align: center;">
        <p style="color:#64748B; font-size:18px;">Menyiapkan sikat gigi dan pasta fluorida...</p>
      </div>
    </div>
  `;

  try {
    sessionData = await api.post('/api/games/sikat-bersih/start', {});
  } catch (e) {
    container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memuat permainan sikat gigi.</p>`;
    return {};
  }

  function getOverallCleanPercent() {
    const total = zones.reduce((sum, z) => sum + z.progress, 0);
    return Math.round((total / (zones.length * 100)) * 100);
  }

  function renderGame() {
    const wrapper = container.firstElementChild.firstElementChild;
    const cleanPercent = getOverallCleanPercent();
    const remainingTime = Math.max(0, totalDuration - elapsedSeconds);
    const minutes = Math.floor(remainingTime / 60);
    const seconds = remainingTime % 60;
    const timeFormatted = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

    wrapper.innerHTML = `
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 16px;">
        <button id="btn-quit" style="background: #F1F5F9; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="font-size: 18px; font-weight: 800; color: var(--color-title);">
          Aksi Sikat Gigi Bersih
        </div>
        <div id="game-mascot-box" style="width: 90px; height: 90px;"></div>
      </div>

      <!-- Indikator Waktu Positif & Total Bersih -->
      <div style="display: flex; justify-content: space-around; align-items: center; background: #E8F6FB; border-radius: 18px; padding: 14px; margin-bottom: 20px;">
        <!-- Cincin Timer Positif -->
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="position: relative; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;">
            <svg width="56" height="56" viewBox="0 0 56 56" style="transform: rotate(-90deg);">
              <circle cx="28" cy="28" r="24" stroke="#CBD5E1" stroke-width="5" fill="none" />
              <circle id="timer-ring" cx="28" cy="28" r="24" stroke="var(--color-sky)" stroke-width="5" fill="none"
                stroke-dasharray="150.8" stroke-dashoffset="${150.8 * (1 - remainingTime / totalDuration)}"
                stroke-linecap="round" style="transition: stroke-dashoffset 1s linear;" />
            </svg>
            <span id="timer-text" style="position: absolute; font-size: 14px; font-weight: 800; color: var(--color-navy);">${timeFormatted}</span>
          </div>
          <div style="text-align: left;">
            <div style="font-size: 13px; color: #64748B; font-weight: 600;">Waktu 2 Menit</div>
            <div style="font-size: 15px; font-weight: 800; color: var(--color-navy);">Gosok Memutar</div>
          </div>
        </div>

        <!-- Persentase Bersih -->
        <div style="text-align: right;">
          <div style="font-size: 13px; color: #64748B; font-weight: 600;">Kebersihan Gigi</div>
          <div id="clean-percent-text" style="font-size: 26px; font-weight: 800; color: #0284C7;">${cleanPercent}%</div>
        </div>
      </div>

      <p style="color: #475569; font-size: 14px; margin: 0 0 16px 0;">
        Arahkan mouse atau sentuh dan gosok gelembung plak kuning pada 6 zona gigi sampai berkilau putih!
      </p>

      <!-- Kanvas / SVG Interaktif Gigi 6 Zona -->
      <div style="position: relative; width: 100%; max-width: 480px; margin: 0 auto 20px auto; background: #FFF; border: 3px solid #D8EEF9; border-radius: 24px; padding: 16px; box-shadow: inset 0 4px 12px rgba(0,0,0,0.03); user-select: none;">
        <svg id="mouth-svg" viewBox="0 0 420 300" style="width: 100%; height: auto; display: block; cursor: crosshair;">
          <!-- Lengkung Mulut & Gusi Merah Muda Lembut -->
          <path d="M 30 150 Q 210 20 390 150 Q 210 280 30 150 Z" fill="#FEE2E2" stroke="#FCA5A5" stroke-width="4" />
          <path d="M 60 150 Q 210 50 360 150 Q 210 250 60 150 Z" fill="#EF4444" opacity="0.15" />

          <!-- Render Tiap Zona Gigi -->
          ${zones.map(z => {
            const plaqueOpacity = Math.max(0, (100 - z.progress) / 100);
            return `
              <g class="zone-group" data-zone-id="${z.id}" style="cursor: pointer;">
                <!-- Gigi Putih -->
                <rect x="${z.x}" y="${z.y}" width="${z.w}" height="${z.h}" rx="16" fill="#FFFFFF" stroke="#94A3B8" stroke-width="2.5" />
                <path d="M ${z.x + 10} ${z.y + z.h/2} Q ${z.x + z.w/2} ${z.y + 10} ${z.x + z.w - 10} ${z.y + z.h/2}" fill="none" stroke="#E2E8F0" stroke-width="2" />

                <!-- Lapisan Plak Kekuningan (Menipis saat digosok) -->
                <rect class="plaque-layer" x="${z.x + 3}" y="${z.y + 3}" width="${z.w - 6}" height="${z.h - 6}" rx="13"
                  fill="#FDE047" opacity="${plaqueOpacity * 0.85}" style="pointer-events: none; transition: opacity 0.1s;" />

                <!-- Gelembung Plak Bintik -->
                <circle cx="${z.x + 25}" cy="${z.y + 25}" r="8" fill="#F59E0B" opacity="${plaqueOpacity}" />
                <circle cx="${z.x + z.w - 25}" cy="${z.y + 35}" r="10" fill="#FBBF24" opacity="${plaqueOpacity}" />
                <circle cx="${z.x + z.w/2}" cy="${z.y + z.h - 20}" r="7" fill="#F59E0B" opacity="${plaqueOpacity}" />

                <!-- Kilau Bintang saat 100% Bersih -->
                <g class="sparkle-layer" style="display: ${z.progress >= 100 ? 'block' : 'none'}; pointer-events: none;">
                  <text x="${z.x + z.w/2 - 10}" y="${z.y + z.h/2 + 8}" font-size="22">✨</text>
                </g>

                <!-- Label Zona -->
                <text x="${z.x + z.w/2}" y="${z.y + z.h + 18}" text-anchor="middle" font-size="11" font-weight="700" fill="#475569">
                  ${z.label}
                </text>
              </g>
            `;
          }).join('')}
        </svg>
      </div>

      <!-- Bantuan Keyboard -->
      <div style="font-size: 12px; color: #64748B; margin-bottom: 16px;">
        💡 Tips: Kamu juga bisa menekan tombol <strong>1 s/d 6</strong> di keyboard untuk menggosok setiap zona!
      </div>
    `;

    // Pasang Dento Maskot mini di header game
    const mascotBox = wrapper.querySelector('#game-mascot-box');
    mascotInstance = createMascot(mascotBox, {
      expression: 'senang',
      pose: 'sikat',
      kebersihan: 90,
      equipped: {}
    }, { size: 90, animated: true });

    // Pasang interaksi gosok pada zona
    const mouthSvg = wrapper.querySelector('#mouth-svg');
    const zoneElements = wrapper.querySelectorAll('.zone-group');

    function rubZone(zoneObj, amount = 4) {
      if (zoneObj.progress >= 100) return;
      zoneObj.progress = Math.min(100, zoneObj.progress + amount);
      sound.playEffect('pop');

      // Update tampilan zona spesifik secara reaktif tanpa re-render seluruh DOM
      const g = wrapper.querySelector(`.zone-group[data-zone-id="${zoneObj.id}"]`);
      if (g) {
        const plaque = g.querySelector('.plaque-layer');
        const circles = g.querySelectorAll('circle');
        const sparkle = g.querySelector('.sparkle-layer');
        const opacity = Math.max(0, (100 - zoneObj.progress) / 100);

        if (plaque) plaque.setAttribute('opacity', (opacity * 0.85).toString());
        circles.forEach(c => c.setAttribute('opacity', opacity.toString()));

        if (zoneObj.progress >= 100) {
          if (sparkle) sparkle.style.display = 'block';
          mascotInstance.set({ expression: 'semangat', pose: 'atas' });
          mascotInstance.react('jump');
        }
      }

      // Update header percent
      const overall = getOverallCleanPercent();
      const percentEl = wrapper.querySelector('#clean-percent-text');
      if (percentEl) percentEl.textContent = `${overall}%`;

      if (overall >= 100) {
        finishGame();
      }
    }

    zoneElements.forEach(zg => {
      const zid = zg.dataset.zoneId;
      const zoneObj = zones.find(z => z.id === zid);

      // Pointermove / Mousemove / Touch
      zg.addEventListener('pointermove', (e) => {
        if (e.buttons === 1 || e.pointerType === 'mouse') {
          rubZone(zoneObj, 3);
        }
      });
      zg.addEventListener('click', () => {
        rubZone(zoneObj, 8);
      });
    });

    // Keyboard support (1..6)
    const keyHandler = (e) => {
      const num = parseInt(e.key, 10);
      if (num >= 1 && num <= 6) {
        const zoneObj = zones[num - 1];
        if (zoneObj) rubZone(zoneObj, 10);
      }
    };
    window.addEventListener('keydown', keyHandler);

    wrapper.querySelector('#btn-quit').addEventListener('click', () => {
      clearInterval(timerInterval);
      window.removeEventListener('keydown', keyHandler);
      router.navigate('home');
    });

    // Mulai Timer Positif
    if (!timerInterval) {
      timerInterval = setInterval(() => {
        elapsedSeconds++;
        const remaining = Math.max(0, totalDuration - elapsedSeconds);
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;

        const timerText = wrapper.querySelector('#timer-text');
        const timerRing = wrapper.querySelector('#timer-ring');
        if (timerText) timerText.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        if (timerRing) {
          const offset = 150.8 * (1 - remaining / totalDuration);
          timerRing.setAttribute('stroke-dashoffset', offset.toString());
        }

        if (remaining <= 0) {
          finishGame();
        }
      }, 1000);
    }
  }

  async function finishGame() {
    clearInterval(timerInterval);
    const score = getOverallCleanPercent();
    const wrapper = container.firstElementChild.firstElementChild;

    wrapper.innerHTML = `
      <p style="font-size:18px; color:var(--color-navy); font-weight:700;">Menghitung kebersihan gigimu...</p>
    `;

    try {
      const resp = await api.post('/api/games/sikat-bersih/finish', {
        game_session_id: sessionData.session_id,
        skor: score,
        detail: {
          duration_seconds: elapsedSeconds,
          zones_duration: zones.map(z => ({ zone: z.id, progress: z.progress })),
        },
      });

      sound.playEffect('jump');

      wrapper.innerHTML = `
        <div style="font-size: 52px; margin-bottom: 12px;">✨🪥✨</div>
        <h2 style="font-size: 26px; font-weight: 800; color: var(--color-title); margin: 0 0 10px 0;">
          Gigimu Bersih & Berkilau!
        </h2>
        <p style="font-size: 16px; color: #475569; margin: 0 0 20px 0;">
          Semua kuman dan plak berhasil dibersihkan dengan gerakan memutar yang luar biasa!
        </p>

        <!-- Kartu Skor & Hadiah -->
        <div style="background: #E8F6FB; border: 2px solid var(--color-sky); border-radius: 20px; padding: 20px; margin-bottom: 24px; display: inline-flex; gap: 28px; justify-content: center;">
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">Hasil Bersih</div>
            <div style="font-size: 26px; font-weight: 800; color: #0284C7;">${resp.skor}%</div>
          </div>
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">Kebersihan Dento</div>
            <div style="font-size: 26px; font-weight: 800; color: #16A34A;">+${resp.kebersihan_tambah}%</div>
          </div>
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">Koin Didapat</div>
            <div style="font-size: 26px; font-weight: 800; color: #B45309;">+${resp.koin_didapat} 🪙</div>
          </div>
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">XP Naik</div>
            <div style="font-size: 26px; font-weight: 800; color: #7C3AED;">+${resp.xp_didapat}</div>
          </div>
        </div>

        <div>
          <button id="btn-back-home" style="min-height: 52px; padding: 12px 32px; background: #2563EB; color: #fff; border: 2px solid #1D4ED8; border-radius: 14px; font-size: 18px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);">
            Kembali ke Rumah Dento &rarr;
          </button>
        </div>
      `;

      wrapper.querySelector('#btn-back-home').addEventListener('click', () => {
        router.navigate('home');
      });
    } catch (e) {
      wrapper.innerHTML = `
        <p style="color:#DC2626; font-size:16px;">Gagal mencatat penyelesaian permainan.</p>
        <button id="btn-err-home" style="padding:10px 20px; background:#F1F5F9; border:none; border-radius:8px; cursor:pointer;">Kembali</button>
      `;
      wrapper.querySelector('#btn-err-home')?.addEventListener('click', () => router.navigate('home'));
    }
  }

  renderGame();

  return {
    destroy() {
      if (timerInterval) clearInterval(timerInterval);
    }
  };
}
