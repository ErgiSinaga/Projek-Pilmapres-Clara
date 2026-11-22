import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createMemoryFaktaGame(container, router) {
  let sessionData = null;
  let mascotInstance = null;
  let cards = [];
  let flippedIndices = [];
  let matchedPairIds = new Set();
  let steps = 0;
  let isLocked = false;

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 24px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="max-width: 760px; width: 100%; background: #ffffff; padding: 32px 24px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); border: 2px solid #E8F6FB; text-align: center;">
        <p style="color:#64748B; font-size:18px;">Menyiapkan kartu fakta seru...</p>
      </div>
    </div>
  `;

  try {
    sessionData = await api.post('/api/games/memory-fakta/start', {});
    cards = sessionData.cards || [];
  } catch (e) {
    container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memuat permainan kartu.</p>`;
    return {};
  }

  function renderGame() {
    const wrapper = container.firstElementChild.firstElementChild;
    const remainingPairs = 6 - matchedPairIds.size;

    wrapper.innerHTML = `
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 16px;">
        <button id="btn-quit" style="background: #F1F5F9; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="font-size: 18px; font-weight: 800; color: var(--color-title);">
          Kartu Memory Fakta Gigi
        </div>
        <div id="game-mascot-box" style="width: 90px; height: 90px;"></div>
      </div>

      <!-- Baris Status Langkah & Sisa Pasangan -->
      <div style="display: flex; justify-content: space-between; align-items: center; background: #FDF2F8; border: 2px solid #FBCFE8; border-radius: 16px; padding: 12px 20px; margin-bottom: 20px;">
        <div style="font-size: 15px; font-weight: 700; color: #9D174D;">
          Langkah Membuka: <span style="font-size: 18px; font-weight: 800;">${steps}</span>
        </div>
        <div id="feedback-text" style="font-size: 14px; font-weight: 700; color: #BE185D;">
          Temukan 6 pasangan kartu yang cocok!
        </div>
        <div style="font-size: 15px; font-weight: 700; color: #9D174D;">
          Sisa: <span style="font-size: 18px; font-weight: 800;">${remainingPairs}</span> pasang
        </div>
      </div>

      <!-- Grid 12 Kartu (4x3 atau 3x4) -->
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px;">
        ${cards.map((c, idx) => {
          const isFlipped = flippedIndices.includes(idx);
          const isMatched = matchedPairIds.has(c.pair_id);

          return `
            <button class="memory-card-btn" data-index="${idx}"
              style="min-height: 100px; padding: 10px; border-radius: 18px; border: 3px solid ${isMatched ? '#10B981' : (isFlipped ? 'var(--color-sky)' : '#CBD5E1')}; background: ${isMatched ? '#ECFDF5' : (isFlipped ? '#FFFFFF' : '#E0F2FE')}; cursor: ${isMatched || isFlipped ? 'default' : 'pointer'}; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s, background 0.2s;">
              ${isFlipped || isMatched ? `
                <span style="font-size: 32px;">${c.icon}</span>
                <span style="font-size: 13px; font-weight: 800; color: ${isMatched ? '#065F46' : 'var(--color-navy)'}; line-height: 1.2; text-align: center;">${c.text}</span>
              ` : `
                <span style="font-size: 28px;">❓</span>
                <span style="font-size: 11px; font-weight: 700; color: #0284C7;">Buka Kartu</span>
              `}
            </button>
          `;
        }).join('')}
      </div>
    `;

    // Pasang Dento Maskot mini
    const mascotBox = wrapper.querySelector('#game-mascot-box');
    mascotInstance = createMascot(mascotBox, {
      expression: 'senang',
      pose: 'tunjuk',
      kebersihan: 95,
      equipped: {}
    }, { size: 90, animated: true });

    wrapper.querySelector('#btn-quit').addEventListener('click', () => {
      router.navigate('home');
    });

    wrapper.querySelectorAll('.memory-card-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (isLocked) return;
        const idx = parseInt(btn.dataset.index, 10);
        const card = cards[idx];

        if (matchedPairIds.has(card.pair_id) || flippedIndices.includes(idx)) {
          return;
        }

        sound.playEffect('pop');
        flippedIndices.push(idx);
        renderGame();

        if (flippedIndices.length === 2) {
          steps++;
          isLocked = true;
          const [idx1, idx2] = flippedIndices;
          const card1 = cards[idx1];
          const card2 = cards[idx2];

          const feedbackEl = container.querySelector('#feedback-text');

          if (card1.pair_id === card2.pair_id) {
            // Cocok!
            matchedPairIds.add(card1.pair_id);
            sound.playEffect('jump');
            mascotInstance.set({ expression: 'semangat', pose: 'atas' });
            mascotInstance.react('jump');

            if (feedbackEl) {
              feedbackEl.textContent = '🎉 Cocok! Pasangan yang tepat!';
            }

            setTimeout(() => {
              flippedIndices = [];
              isLocked = false;
              if (matchedPairIds.size === 6) {
                finishGame();
              } else {
                renderGame();
              }
            }, 600);
          } else {
            // Mismatch
            mascotInstance.set({ expression: 'bingung', pose: 'tunjuk' });
            mascotInstance.react('wobble');

            if (feedbackEl) {
              feedbackEl.textContent = 'Coba lagi ya, ingat posisinya!';
            }

            setTimeout(() => {
              flippedIndices = [];
              isLocked = false;
              renderGame();
            }, 1000);
          }
        }
      });
    });
  }

  async function finishGame() {
    // Skor dihitung berdasarkan langkah (makin sedikit langkah makin tinggi)
    let score = 12;
    if (steps > 18) score = 6;
    else if (steps > 14) score = 8;
    else if (steps > 10) score = 10;

    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <p style="font-size:18px; color:var(--color-navy); font-weight:700;">Menyimpan fakta hebatmu...</p>
    `;

    try {
      const resp = await api.post('/api/games/memory-fakta/finish', {
        game_session_id: sessionData.session_id,
        skor: score,
        detail: { steps: steps, matched_pairs: 6 },
      });

      sound.playEffect('jump');

      wrapper.innerHTML = `
        <div style="font-size: 52px; margin-bottom: 12px;">🧠✨🏆</div>
        <h2 style="font-size: 26px; font-weight: 800; color: var(--color-title); margin: 0 0 10px 0;">
          Daya Ingat Hebat!
        </h2>
        <p style="font-size: 16px; color: #475569; margin: 0 0 20px 0;">
          Kamu berhasil mencocokkan semua fakta kesehatan gigi dalam <strong>${steps}</strong> langkah!
        </p>

        <div style="background: #FDF2F8; border: 2px solid #FBCFE8; border-radius: 20px; padding: 20px; margin-bottom: 24px; display: inline-flex; gap: 28px; justify-content: center;">
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">Kebahagiaan</div>
            <div style="font-size: 26px; font-weight: 800; color: #DB2777;">+${resp.kebahagiaan_tambah}%</div>
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
  return {};
}
