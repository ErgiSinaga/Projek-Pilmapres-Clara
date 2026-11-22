import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createKuisKilatGame(container, router) {
  let sessionData = null;
  let mascotInstance = null;
  let questions = [];
  let currentIndex = 0;
  let correctCount = 0;
  let selectedOption = null;
  let answered = false;

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 24px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="max-width: 680px; width: 100%; background: #ffffff; padding: 32px 24px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); border: 2px solid #E8F6FB; text-align: center;">
        <p style="color:#64748B; font-size:18px;">Menyiapkan pertanyaan kuis kilat...</p>
      </div>
    </div>
  `;

  try {
    sessionData = await api.post('/api/games/kuis-kilat/start', {});
    questions = sessionData.questions || [];
  } catch (e) {
    container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memuat kuis kilat.</p>`;
    return {};
  }

  function renderQuestion() {
    if (currentIndex >= questions.length) {
      renderFinished();
      return;
    }

    const currentQ = questions[currentIndex];
    answered = false;
    selectedOption = null;

    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 20px;">
        <button id="btn-quit" style="background: #F1F5F9; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="font-size: 18px; font-weight: 800; color: var(--color-title);">
          Kuis Kilat Gigi Sehat (${currentIndex + 1} / ${questions.length})
        </div>
        <div id="game-mascot-box" style="width: 90px; height: 90px;"></div>
      </div>

      <!-- Kotak Pertanyaan -->
      <div style="background: #E8F6FB; border: 2.5px solid var(--color-sky); border-radius: 20px; padding: 22px; margin-bottom: 24px; text-align: left;">
        <span style="display: inline-block; background: var(--color-sky-dark); color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 800; margin-bottom: 8px;">
          ${currentQ.tp || 'Kuis Gigi'}
        </span>
        <h3 style="font-size: 20px; font-weight: 800; color: var(--color-navy); margin: 0; line-height: 1.4;">
          ${currentQ.pertanyaan}
        </h3>
      </div>

      <!-- Pilihan Jawaban -->
      <div id="options-container" style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;">
        ${currentQ.opsi.map((opsiText, optIdx) => `
          <button class="quiz-option-btn" data-index="${optIdx}"
            style="min-height: 60px; padding: 14px 20px; border-radius: 16px; border: 2px solid #CBD5E1; background: #FFFFFF; font-size: 16px; font-weight: 700; color: var(--color-navy); text-align: left; cursor: pointer; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.2s;">
            <span style="width: 32px; height: 32px; border-radius: 50%; background: #F1F5F9; border: 1.5px solid #CBD5E1; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #475569;">
              ${optIdx + 1}
            </span>
            <span style="flex: 1; line-height: 1.3;">${opsiText}</span>
          </button>
        `).join('')}
      </div>

      <!-- Feedback Bubble Langsung -->
      <div id="feedback-card" style="display: none; border-radius: 16px; padding: 16px 20px; margin-bottom: 20px; text-align: left;">
        <div id="feedback-title" style="font-size: 16px; font-weight: 800; margin-bottom: 6px;"></div>
        <div id="feedback-reason" style="font-size: 14px; line-height: 1.4; color: #334155;"></div>
      </div>

      <!-- Tombol Lanjut -->
      <div id="action-container" style="display: none;">
        <button id="btn-next" style="min-height: 52px; padding: 12px 32px; background: #2563EB; color: #fff; border: 2px solid #1D4ED8; border-radius: 14px; font-size: 17px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);">
          ${currentIndex + 1 < questions.length ? 'Soal Berikutnya &rarr;' : 'Lihat Hasil Kuis &rarr;'}
        </button>
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

    const optionBtns = wrapper.querySelectorAll('.quiz-option-btn');
    const feedbackCard = wrapper.querySelector('#feedback-card');
    const feedbackTitle = wrapper.querySelector('#feedback-title');
    const feedbackReason = wrapper.querySelector('#feedback-reason');
    const actionContainer = wrapper.querySelector('#action-container');
    const nextBtn = wrapper.querySelector('#btn-next');

    optionBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        if (answered) return;
        answered = true;
        const pickedIdx = parseInt(btn.dataset.index, 10);
        const isCorrect = (pickedIdx === currentQ.benar);

        if (isCorrect) {
          correctCount++;
          sound.playEffect('jump');
          mascotInstance.set({ expression: 'semangat', pose: 'atas' });
          mascotInstance.react('jump');

          btn.style.border = '2.5px solid #10B981';
          btn.style.background = '#ECFDF5';
          btn.style.color = '#065F46';

          feedbackCard.style.display = 'block';
          feedbackCard.style.background = '#ECFDF5';
          feedbackCard.style.border = '2px solid #A7F3D0';
          feedbackTitle.style.color = '#065F46';
          feedbackTitle.textContent = '🎉 Hebat sekali! Jawabanmu Tepat!';
        } else {
          sound.playEffect('pop');
          mascotInstance.set({ expression: 'bingung', pose: 'tunjuk' });
          mascotInstance.react('wobble');

          btn.style.border = '2.5px solid #EF4444';
          btn.style.background = '#FEF2F2';
          btn.style.color = '#991B1B';

          // Sorot jawaban yang benar
          const correctBtn = optionBtns[currentQ.benar];
          if (correctBtn) {
            correctBtn.style.border = '2.5px solid #10B981';
            correctBtn.style.background = '#ECFDF5';
          }

          feedbackCard.style.display = 'block';
          feedbackCard.style.background = '#FFFBEB';
          feedbackCard.style.border = '2px solid #FCD34D';
          feedbackTitle.style.color = '#92400E';
          feedbackTitle.textContent = '💡 Penjelasan:';
        }

        feedbackReason.textContent = currentQ.alasan || 'Ingat selalu menjaga kebersihan gigimu ya!';
        actionContainer.style.display = 'block';
      });
    });

    nextBtn.addEventListener('click', () => {
      currentIndex++;
      renderQuestion();
    });
  }

  async function renderFinished() {
    const wrapper = container.firstElementChild.firstElementChild;
    wrapper.innerHTML = `
      <p style="font-size:18px; color:var(--color-navy); font-weight:700;">Menghitung hasil kuis kilatmu...</p>
    `;

    try {
      const resp = await api.post('/api/games/kuis-kilat/finish', {
        game_session_id: sessionData.session_id,
        skor: correctCount,
        detail: { correct_count: correctCount, total: questions.length },
      });

      sound.playEffect('jump');

      wrapper.innerHTML = `
        <div style="font-size: 52px; margin-bottom: 12px;">🌟⚡🎯</div>
        <h2 style="font-size: 26px; font-weight: 800; color: var(--color-title); margin: 0 0 10px 0;">
          Kuis Kilat Selesai!
        </h2>
        <p style="font-size: 16px; color: #475569; margin: 0 0 20px 0;">
          Kamu berhasil menjawab benar <strong>${correctCount} dari ${questions.length}</strong> pertanyaan!
        </p>

        <div style="background: #E8F6FB; border: 2px solid var(--color-sky); border-radius: 20px; padding: 20px; margin-bottom: 24px; display: inline-flex; gap: 28px; justify-content: center;">
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">Benar</div>
            <div style="font-size: 26px; font-weight: 800; color: #0284C7;">${correctCount} / 5</div>
          </div>
          <div>
            <div style="font-size: 13px; color: #64748B; font-weight: 700;">Kebahagiaan</div>
            <div style="font-size: 26px; font-weight: 800; color: #E85D5D;">+${resp.kebahagiaan_tambah}%</div>
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
        <p style="color:#DC2626; font-size:16px;">Gagal mencatat penyelesaian kuis.</p>
        <button id="btn-err-home" style="padding:10px 20px; background:#F1F5F9; border:none; border-radius:8px; cursor:pointer;">Kembali</button>
      `;
      wrapper.querySelector('#btn-err-home')?.addEventListener('click', () => router.navigate('home'));
    }
  }

  renderQuestion();
  return {};
}
