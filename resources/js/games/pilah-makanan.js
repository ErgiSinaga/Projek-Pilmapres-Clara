import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createPilahMakananGame(container, router) {
  let sessionData = null;
  let mascotInstance = null;
  let items = [];
  let currentIndex = 0;
  let correctCount = 0;
  let roundStartTime = Date.now();
  let retryItems = [];

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 24px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="max-width: 680px; width: 100%; background: #ffffff; padding: 32px 24px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); border: 2px solid #E8F6FB; text-align: center; position: relative;">
        <p style="color:#64748B; font-size:18px;">Menyiapkan keranjang makanan...</p>
      </div>
    </div>
  `;

  try {
    sessionData = await api.post('/api/games/pilah-makanan/start', {});
    items = sessionData.items || [];
  } catch (e) {
    container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memulai permainan.</p>`;
    return {};
  }

  function renderGame() {
    if (currentIndex >= items.length) {
      if (retryItems.length > 0) {
        items = items.concat(retryItems);
        retryItems = [];
      } else {
        renderFinished();
        return;
      }
    }

    const currentItem = items[currentIndex];
    roundStartTime = Date.now();

    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 20px;">
        <button id="btn-quit" style="background: #F1F5F9; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="font-size: 18px; font-weight: 800; color: var(--color-title);">
          Pilah Makanan (${currentIndex + 1} / ${items.length})
        </div>
        <div id="game-mascot-box" style="width: 100px; height: 100px;"></div>
      </div>

      <!-- Bubble Alasan jika salah -->
      <div id="feedback-bubble" style="display:none; background:#FFFBEB; border:2px solid #FCD34D; border-radius:14px; padding:12px 18px; margin-bottom:18px; font-size:15px; font-weight:700; color:#78350F; line-height:1.4;"></div>

      <!-- Kartu Makanan Tengah -->
      <div style="margin: 20px 0 32px 0; display: flex; justify-content: center;">
        <div style="background: #E8F6FB; border: 3px solid var(--color-sky); border-radius: 24px; padding: 24px; width: 220px; box-shadow: 0 8px 20px rgba(74, 168, 216, 0.2); transition: transform 0.2s;">
          <div style="font-size: 54px; margin-bottom: 8px;">🍎</div>
          <div style="font-size: 22px; font-weight: 800; color: var(--color-navy); line-height: 1.3;">
            ${currentItem.nama}
          </div>
          <div style="font-size: 13px; color: #64748B; margin-top: 6px;">Pilih keranjang yang tepat:</div>
        </div>
      </div>

      <!-- Dua Keranjang Pilihan -->
      <div style="display: flex; gap: 20px; justify-content: center;">
        <!-- Keranjang Gigi Senang -->
        <button id="btn-senang"
          style="flex: 1; min-height: 80px; padding: 16px; background: #ECFDF5; border: 3px solid #10B981; border-radius: 20px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transition: transform 0.1s;">
          <span style="font-size: 28px;">😁</span>
          <span style="font-size: 18px; font-weight: 800; color: #065F46;">Gigi Senang</span>
          <span style="font-size: 12px; color: #047857;">(Makanan Baik)</span>
        </button>

        <!-- Keranjang Gigi Sedih -->
        <button id="btn-sedih"
          style="flex: 1; min-height: 80px; padding: 16px; background: #FEF2F2; border: 3px solid #EF4444; border-radius: 20px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); transition: transform 0.1s;">
          <span style="font-size: 28px;">😢</span>
          <span style="font-size: 18px; font-weight: 800; color: #991B1B;">Gigi Sedih</span>
          <span style="font-size: 12px; color: #B91C1C;">(Merusak Gigi)</span>
        </button>
      </div>

      <div style="margin-top: 24px; font-size: 13px; color: #94A3B8;">
        Gunakan tombol atau tekan tombol keyboard: <strong>1</strong> (Gigi Senang) atau <strong>2</strong> (Gigi Sedih)
      </div>
    `;

    const mascotBox = wrapper.querySelector('#game-mascot-box');
    mascotInstance = createMascot(mascotBox, {
      expression: 'senang',
      pose: 'turun',
      kebersihan: 100,
    }, { size: 100, track: false });

    wrapper.querySelector('#btn-quit').addEventListener('click', () => router.navigate('home'));

    const btnSenang = wrapper.querySelector('#btn-senang');
    const btnSedih = wrapper.querySelector('#btn-sedih');

    const handleChoice = async (chosenCategory) => {
      btnSenang.disabled = true;
      btnSedih.disabled = true;

      const elapsedMs = Date.now() - roundStartTime;
      const isCorrect = (chosenCategory === currentItem.kategori);

      if (isCorrect) {
        correctCount++;
        sound.playEffect('jump');
        mascotInstance.set({ expression: 'semangat', pose: 'atas' });
        mascotInstance.react('jump');
      } else {
        mascotInstance.set({ expression: 'bingung', pose: 'tunjuk' });
        mascotInstance.react('wobble');
        retryItems.push(currentItem);

        const bubble = wrapper.querySelector('#feedback-bubble');
        bubble.textContent = `💡 Coba perhatikan lagi: ${currentItem.alasan}`;
        bubble.style.display = 'block';
      }

      // Log event ke server
      api.post('/api/games/pilah-makanan/event', {
        game_session_id: sessionData.session_id,
        tipe: 'food_choice',
        payload: {
          food_id: currentItem.id,
          nama: currentItem.nama,
          pilihan: chosenCategory,
          benar: isCorrect,
          ms: elapsedMs,
        },
      }).catch(() => {});

      setTimeout(() => {
        currentIndex++;
        renderGame();
      }, isCorrect ? 700 : 2000);
    };

    btnSenang.addEventListener('click', () => handleChoice('baik'));
    btnSedih.addEventListener('click', () => handleChoice('merusak'));

    // Keyboard support: 1 or ArrowLeft for Baik, 2 or ArrowRight for Merusak
    const keyHandler = (e) => {
      if (btnSenang.disabled) return;
      if (e.key === '1' || e.key === 'ArrowLeft') {
        window.removeEventListener('keydown', keyHandler);
        handleChoice('baik');
      } else if (e.key === '2' || e.key === 'ArrowRight') {
        window.removeEventListener('keydown', keyHandler);
        handleChoice('merusak');
      }
    };
    window.addEventListener('keydown', keyHandler, { once: true });
  }

  async function renderFinished() {
    if (mascotInstance) mascotInstance.destroy();

    let finishRes = null;
    try {
      finishRes = await api.post('/api/games/pilah-makanan/finish', {
        game_session_id: sessionData.session_id,
        skor: correctCount,
        detail: { total_items: 10, correct_count: correctCount },
      });
    } catch (e) {
      //
    }

    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <div style="text-align: center; padding: 24px 16px;">
        <div id="finish-mascot-box" style="display:flex; justify-content:center; height:180px; margin-bottom:16px;"></div>

        <h2 style="color: var(--color-title); font-size: 28px; font-weight: 800; margin: 0 0 8px 0;">Hebat Sekali!</h2>
        <p style="color: #475569; font-size: 18px; margin: 0 0 16px 0;">
          Kamu berhasil memilah makanan ramah gigi dengan skor <strong>${correctCount} / 10</strong>!
        </p>

        <div style="background: #FFFBEB; border: 2px solid #FCD34D; border-radius: 16px; padding: 16px; margin: 0 auto 24px auto; max-width: 320px; display: flex; justify-content: space-around;">
          <div>
            <div style="font-size: 12px; color: #92400E; font-weight: 700;">KOIN DIDAPAT</div>
            <div style="font-size: 24px; font-weight: 800; color: #B45309;">+${finishRes ? finishRes.koin_didapat : correctCount * 2} 🪙</div>
          </div>
          <div style="border-left: 2px solid #FDE68A;"></div>
          <div>
            <div style="font-size: 12px; color: #92400E; font-weight: 700;">XP DENTO</div>
            <div style="font-size: 24px; font-weight: 800; color: #2563EB;">+10 XP ⭐</div>
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
      expression: 'semangat',
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
