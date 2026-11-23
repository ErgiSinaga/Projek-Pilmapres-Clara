import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createUrutanSikatGame(container, router) {
  let sessionData = null;
  let mascotInstance = null;
  let allSteps = [];
  let placedSteps = []; // slots 0..5
  let availableSteps = [];
  let attempts = 0;

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 24px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="max-width: 760px; width: 100%; background: #ffffff; padding: 32px 24px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); border: 2px solid #E8F6FB; text-align: center;">
        <p style="color:#64748B; font-size:18px;">Menyiapkan langkah sikat gigi...</p>
      </div>
    </div>
  `;

  try {
    sessionData = await api.post('/api/games/urutan-sikat/start', {});
    allSteps = sessionData.steps || [];
    availableSteps = [...allSteps];
  } catch (e) {
    container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memuat permainan.</p>`;
    return {};
  }

  function renderGame() {
    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 20px;">
        <button id="btn-quit" style="background: #F1F5F9; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="font-size: 18px; font-weight: 800; color: var(--color-title);">
          Urutan Menyikat Gigi yang Tepat
        </div>
        <div id="game-mascot-box" style="width: 100px; height: 100px;"></div>
      </div>

      <div id="alert-box" style="display:none; background:#FFFBEB; border:2px solid #FCD34D; border-radius:14px; padding:12px; margin-bottom:16px; font-size:15px; font-weight:700; color:#78350F;"></div>

      <p style="color:#475569; font-size:15px; margin:0 0 16px 0; text-align:left;">
        Klik kartu di bawah secara berurutan untuk mengisi slot langkah 1 sampai 6:
      </p>

      <!-- 6 Slot Terisi -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 24px;">
        ${[0, 1, 2, 3, 4, 5].map(idx => {
          const step = placedSteps[idx];
          return `
            <div class="placed-slot" data-index="${idx}"
              style="min-height: 72px; padding: 10px 14px; border-radius: 14px; border: 2px dashed ${step ? 'var(--color-sky)' : '#CBD5E1'}; background: ${step ? '#E8F6FB' : '#F8FAFC'}; display: flex; align-items: center; gap: 10px; text-align: left; cursor: ${step ? 'pointer' : 'default'};">
              <span style="font-size: 16px; font-weight: 800; color: var(--color-sky); background: #fff; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1.5px solid var(--color-sky);">
                ${idx + 1}
              </span>
              <span style="font-size: 14px; font-weight: 700; color: var(--color-navy); flex: 1;">
                ${step ? step.judul : '<span style="color:#94A3B8; font-weight:500;">(Slot kosong - klik kartu)</span>'}
              </span>
              ${step ? '<span style="color:#EF4444; font-size:14px;">✕</span>' : ''}
            </div>
          `;
        }).join('')}
      </div>

      <!-- Kartu Pilihan Tersedia -->
      <div style="background: #F8FAFC; border: 2px solid #E2E8F0; border-radius: 18px; padding: 16px; margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 800; color: #64748B; text-transform: uppercase; margin-bottom: 12px; text-align: left;">
          Pilihan Kartu Langkah:
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
          ${availableSteps.map(step => `
            <button class="step-card-btn" data-id="${step.id}"
              style="min-height: 56px; padding: 12px; background: #fff; border: 2px solid #CBD5E1; border-radius: 12px; font-size: 14px; font-weight: 700; color: var(--color-navy); cursor: pointer; text-align: left; transition: all 0.15s; display: flex; align-items: center;">
              ${step.judul}
            </button>
          `).join('')}
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center;">
        <button id="btn-reset-order" style="padding: 10px 18px; background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 10px; font-weight: 700; color: #475569; cursor: pointer;">
          Ulangi Pilihan
        </button>

        <button id="btn-check-order" ${placedSteps.length === 6 ? '' : 'disabled'}
          style="min-height: 52px; padding: 12px 32px; background: ${placedSteps.length === 6 ? '#059669' : '#94A3B8'}; color: #fff; border: none; border-radius: 12px; font-size: 18px; font-weight: 800; cursor: ${placedSteps.length === 6 ? 'pointer' : 'not-allowed'}; box-shadow: ${placedSteps.length === 6 ? '0 4px 12px rgba(5,150,105,0.3)' : 'none'};">
          Periksa Urutan
        </button>
      </div>
    `;

    const mascotBox = wrapper.querySelector('#game-mascot-box');
    mascotInstance = createMascot(mascotBox, {
      expression: 'senang',
      pose: 'tunjuk',
      kebersihan: 100,
    }, { size: 100, track: false });

    wrapper.querySelector('#btn-quit').addEventListener('click', () => router.navigate('home'));

    // Klik kartu untuk menaruh di slot berikutnya
    wrapper.querySelectorAll('.step-card-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        if (placedSteps.length >= 6) return;
        const stepId = parseInt(btn.dataset.id, 10);
        const stepObj = availableSteps.find(s => s.id === stepId);
        if (stepObj) {
          placedSteps.push(stepObj);
          availableSteps = availableSteps.filter(s => s.id !== stepId);
          renderGame();
        }
      });
    });

    // Klik slot terisi untuk melepas kembali
    wrapper.querySelectorAll('.placed-slot').forEach(slot => {
      slot.addEventListener('click', () => {
        const idx = parseInt(slot.dataset.index, 10);
        if (placedSteps[idx]) {
          const removed = placedSteps.splice(idx, 1)[0];
          availableSteps.push(removed);
          renderGame();
        }
      });
    });

    wrapper.querySelector('#btn-reset-order').addEventListener('click', () => {
      placedSteps = [];
      availableSteps = [...allSteps];
      renderGame();
    });

    wrapper.querySelector('#btn-check-order').addEventListener('click', async () => {
      if (placedSteps.length !== 6) return;
      attempts++;

      // Cek apakah urutannya 1, 2, 3, 4, 5, 6
      const isCorrect = placedSteps.every((step, i) => step.urutan === (i + 1));
      const alertBox = wrapper.querySelector('#alert-box');

      if (isCorrect) {
        sound.playEffect('jump');
        mascotInstance.set({ expression: 'semangat', pose: 'atas' });
        mascotInstance.react('jump');

        setTimeout(async () => {
          await renderFinished(12);
        }, 800);
      } else {
        mascotInstance.set({ expression: 'bingung', pose: 'tunjuk' });
        mascotInstance.react('wobble');

        if (attempts >= 3) {
          alertBox.textContent = '💡 Urutan yang benar: Mulai dari oles pasta gigi, sikat depan memutar, sikat geraham kunyah, bagian dalam, sikat lidah, lalu berkumur!';
          alertBox.style.display = 'block';

          setTimeout(async () => {
            await renderFinished(8); // skor parsial
          }, 2500);
        } else {
          alertBox.innerHTML = `Masih ada langkah yang terbalik. Coba lagi ya! (Percobaan ${attempts} dari 3)${attempts >= 2 ? '<br><button id="btn-watch-brush-video" style="margin-top:8px; padding:6px 14px; background:#0D9488; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:800; cursor:pointer;">🎬 Tonton Video Cara Sikat (M1)</button>' : ''}`;
          alertBox.style.display = 'block';
          alertBox.querySelector('#btn-watch-brush-video')?.addEventListener('click', () => {
            router.navigate('videos?v=M1-sikat-malam&src=petunjuk_game');
          });
        }
      }
    });
  }

  async function renderFinished(score) {
    if (mascotInstance) mascotInstance.destroy();

    let finishRes = null;
    try {
      finishRes = await api.post('/api/games/urutan-sikat/finish', {
        game_session_id: sessionData.session_id,
        skor: score,
        detail: { attempts, is_correct: score === 12 },
      });
    } catch (e) {
      //
    }

    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <div style="text-align: center; padding: 24px 16px;">
        <div id="finish-mascot-box" style="display:flex; justify-content:center; height:180px; margin-bottom:16px;"></div>

        <h2 style="color: var(--color-title); font-size: 28px; font-weight: 800; margin: 0 0 8px 0;">Urutan Sikat Gigi Selesai!</h2>
        <p style="color: #475569; font-size: 18px; margin: 0 0 16px 0;">
          Sekarang kamu sudah hafal langkah-langkah menyikat gigi yang tepat!
        </p>

        <div style="background: #FFFBEB; border: 2px solid #FCD34D; border-radius: 16px; padding: 16px; margin: 0 auto 24px auto; max-width: 320px; display: flex; justify-content: space-around;">
          <div>
            <div style="font-size: 12px; color: #92400E; font-weight: 700;">KOIN DIDAPAT</div>
            <div style="font-size: 24px; font-weight: 800; color: #B45309;">+${finishRes ? finishRes.koin_didapat : score} 🪙</div>
          </div>
          <div style="border-left: 2px solid #FDE68A;"></div>
          <div>
            <div style="font-size: 12px; color: #92400E; font-weight: 700;">KEBERSIHAN</div>
            <div style="font-size: 24px; font-weight: 800; color: #059669;">+10% 🪥</div>
          </div>
        </div>

        <button id="btn-return"
          style="min-height: 56px; padding: 14px 40px; background: #059669; color: #fff; border: none; border-radius: 14px; font-size: 20px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(5,150,105,0.3);">
          Kembali ke Rumah Dento &rarr;
        </button>
      </div>
    `;

    const finishBox = wrapper.querySelector('#finish-mascot-box');
    mascotInstance = createMascot(finishBox, {
      expression: 'bersih',
      pose: 'atas',
      kebersihan: 100,
    }, { size: 180, track: true });
    mascotInstance.react('pop');

    wrapper.querySelector('#btn-return').addEventListener('click', () => {
      router.navigate('home');
    });
  }

  renderGame();

  return {
    destroy() {
      if (mascotInstance) mascotInstance.destroy();
    },
  };
}
