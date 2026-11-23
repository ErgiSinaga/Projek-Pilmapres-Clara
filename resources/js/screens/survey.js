import { api } from '../api.js';

// Wajah SVG buatan sendiri untuk skala kepuasan 1-5 (bukan emoji font)
function faceSVG(score, selected = false) {
  const bg = selected ? '#E8F6FB' : '#FFFFFF';
  const border = selected ? '#4AA8D8' : '#CBD5E1';
  const stroke = selected ? '#1F6E9C' : '#64748B';

  let mouthPath = '';
  let eyes = `
    <circle cx="16" cy="18" r="2.5" fill="${stroke}"/>
    <circle cx="32" cy="18" r="2.5" fill="${stroke}"/>
  `;

  if (score === 1) {
    // Sangat Sedih
    mouthPath = `<path d="M14 36 Q24 24 34 36" fill="none" stroke="${stroke}" stroke-width="3" stroke-linecap="round"/>`;
  } else if (score === 2) {
    // Sedikit Sedih / Kecewa
    mouthPath = `<path d="M16 33 Q24 28 32 33" fill="none" stroke="${stroke}" stroke-width="3" stroke-linecap="round"/>`;
  } else if (score === 3) {
    // Netral / Datar
    mouthPath = `<line x1="16" y1="31" x2="32" y2="31" stroke="${stroke}" stroke-width="3" stroke-linecap="round"/>`;
  } else if (score === 4) {
    // Senyum
    mouthPath = `<path d="M16 28 Q24 36 32 28" fill="none" stroke="${stroke}" stroke-width="3" stroke-linecap="round"/>`;
  } else {
    // Senyum Lebar / Sangat Senang
    mouthPath = `
      <path d="M14 27 Q24 40 34 27 Z" fill="#FFB3C1" stroke="${stroke}" stroke-width="2.5"/>
    `;
    eyes = `
      <path d="M12 18 Q16 12 20 18" fill="none" stroke="${stroke}" stroke-width="2.5" stroke-linecap="round"/>
      <path d="M28 18 Q32 12 36 18" fill="none" stroke="${stroke}" stroke-width="2.5" stroke-linecap="round"/>
    `;
  }

  return `
    <svg viewBox="0 0 48 48" width="44" height="44" style="background:${bg}; border-radius:50%; border:2px solid ${border}; padding:4px;">
      <circle cx="24" cy="24" r="22" fill="${bg}"/>
      ${eyes}
      ${mouthPath}
    </svg>
  `;
}

export async function createSurveyScreen(container, router) {
  let surveyData = null;
  const answers = {};

  container.innerHTML = `
    <div style="min-height: 100vh; padding: 32px 16px; display: flex; justify-content: center; box-sizing: border-box;">
      <div style="background: #ffffff; padding: 36px 32px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); max-width: 680px; width: 100%; border: 2px solid #E8F6FB;">
        <h1 style="color: var(--color-title); font-size: 28px; font-weight: 800; margin: 0 0 8px 0; text-align: center;">Kuesioner Sahabat Dento</h1>
        <p style="color: #64748B; font-size: 16px; margin: 0 0 28px 0; text-align: center;">
          Bagaimana perasaanmu setelah bermain dan belajar bersama Dento? Pilih wajah yang paling mewakili perasaanmu ya!
        </p>

        <div id="survey-items" style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 32px;"></div>

        <div style="display: flex; justify-content: center;">
          <button id="btn-submit-survey" disabled
            style="min-height: 56px; padding: 14px 40px; background: #94A3B8; color: #fff; border: none; border-radius: 14px; font-size: 19px; font-weight: 700; cursor: not-allowed; transition: all 0.2s;">
            Kirim Jawaban & Selesai
          </button>
        </div>
      </div>
    </div>
  `;

  try {
    surveyData = await api.get('/api/survey');
  } catch (err) {
    container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memuat kuesioner.</p>`;
    return {};
  }

  const itemsContainer = container.querySelector('#survey-items');
  const submitBtn = container.querySelector('#btn-submit-survey');
  const labels = ['Sangat Tidak Suka', 'Kurang Suka', 'Biasa Saja', 'Suka', 'Sangat Suka'];

  function renderItems() {
    itemsContainer.innerHTML = surveyData.questions.map((q, idx) => `
      <div style="background: #F8FAFC; padding: 18px; border-radius: 16px; border: 1px solid #E2E8F0;">
        <div style="font-size: 17px; font-weight: 700; color: var(--color-navy); margin-bottom: 14px;">
          ${idx + 1}. ${q.teks}
        </div>
        <div style="display: flex; justify-content: space-between; gap: 8px;">
          ${[1, 2, 3, 4, 5].map(score => `
            <button class="score-btn" data-kode="${q.kode}" data-score="${score}" title="${labels[score - 1]}"
              style="flex: 1; min-height: 56px; border: none; background: transparent; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 4px;">
              ${faceSVG(score, answers[q.kode] === score)}
              <span style="font-size: 11px; color: ${answers[q.kode] === score ? 'var(--color-title)' : '#64748B'}; font-weight: ${answers[q.kode] === score ? '700' : '500'};">
                ${score}
              </span>
            </button>
          `).join('')}
        </div>
      </div>
    `).join('');

    itemsContainer.querySelectorAll('.score-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const kode = btn.dataset.kode;
        const score = parseInt(btn.dataset.score, 10);
        answers[kode] = score;
        renderItems();
        checkCompleteness();
      });
    });
  }

  function checkCompleteness() {
    const isComplete = surveyData.questions.every(q => answers[q.kode] !== undefined);
    if (isComplete) {
      submitBtn.disabled = false;
      submitBtn.style.background = '#059669';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.boxShadow = '0 4px 12px rgba(5, 150, 105, 0.3)';
    }
  }

  submitBtn.addEventListener('click', async () => {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengirim...';

    const payload = Object.entries(answers).map(([kode, nilai]) => ({
      kode,
      nilai,
    }));

    try {
      await api.post('/api/survey', { responses: payload });
      alert('Terima kasih banyak atas penilaianmu! Yuk tonton video penutup spesial dari Dento!');
      router.navigate('videos?v=VP-penutup&src=penutup');
    } catch (err) {
      alert('Gagal mengirim kuesioner. Silakan coba lagi.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Kirim Jawaban & Selesai';
    }
  });

  renderItems();

  return {};
}
