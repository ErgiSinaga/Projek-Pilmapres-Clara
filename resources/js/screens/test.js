import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';

export async function createTestScreen(container, router, params) {
  const jenis = params.get('jenis') || 'pre';
  const jenisTitle = jenis === 'pre' ? 'Pre-Test' : 'Post-Test';
  let mascotInstance = null;
  let testData = null;
  let currentIndex = 0;
  let questionStartTime = Date.now();

  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box;">
      <div style="background: #ffffff; padding: 32px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); max-width: 680px; width: 100%; border: 2px solid #E8F6FB; position: relative;">
        <div id="test-content">
          <p style="color: #64748B; font-size: 18px; text-align: center;">Menyiapkan soal ${jenisTitle}...</p>
        </div>
      </div>
    </div>
  `;

  try {
    testData = await api.get(`/api/test/${jenis}/start`);
  } catch (err) {
    container.querySelector('#test-content').innerHTML = `
      <div style="text-align: center; padding: 20px;">
        <h2 style="color: #EF4444; font-size: 24px; margin-bottom: 12px;">Pemberitahuan</h2>
        <p style="color: #475569; font-size: 17px; margin-bottom: 24px;">${err.message || 'Tes tidak dapat dimuat.'}</p>
        <button id="btn-back" style="min-height: 52px; padding: 12px 24px; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer;">
          Kembali ke Beranda
        </button>
      </div>
    `;
    container.querySelector('#btn-back')?.addEventListener('click', () => router.navigate('home'));
    return {};
  }

  currentIndex = testData.current_index || 0;

  function renderQuestion() {
    const questions = testData.questions;
    if (currentIndex >= questions.length) {
      renderCompleted();
      return;
    }

    const q = questions[currentIndex];
    questionStartTime = Date.now();

    const content = container.querySelector('#test-content');
    content.innerHTML = `
      <!-- Header Tes -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 16px; margin-bottom: 20px;">
        <div>
          <span style="font-size: 14px; font-weight: 700; color: var(--color-sky); text-transform: uppercase; letter-spacing: 1px;">${jenisTitle}</span>
          <div style="font-size: 20px; font-weight: 800; color: var(--color-navy);">Soal ${currentIndex + 1} dari ${questions.length}</div>
        </div>
        <div id="mascot-test-host" style="width: 120px; height: 120px;"></div>
      </div>

      <!-- Pesan Menenangkan dari Dento -->
      <div style="background: #E8F6FB; border-left: 4px solid var(--color-sky); padding: 10px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; color: var(--color-navy);">
        💬 <em>"Ini bukan ujian berhitung, jawab dengan tenang sesuai yang kamu ketahui ya!"</em>
      </div>

      <!-- Soal -->
      <div style="margin-bottom: 24px;">
        <h3 style="font-size: 20px; font-weight: 700; color: #1E293B; line-height: 1.5; margin: 0 0 12px 0;">
          ${q.teks}
        </h3>
        ${q.audio ? `
          <button id="btn-audio" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 8px; background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 12px;">
            🔊 Dengarkan Soal
          </button>
        ` : ''}
      </div>

      <!-- Pilihan Jawaban -->
      <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
        ${q.opsi.map((opt, oIdx) => `
          <button class="option-btn" data-index="${oIdx}"
            style="min-height: 56px; padding: 14px 18px; border-radius: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; color: var(--color-navy); font-size: 17px; font-weight: 600; text-align: left; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 14px;">
            <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #F1F5F9; font-weight: 700; font-size: 15px; color: #475569;">
              ${String.fromCharCode(65 + oIdx)}
            </span>
            <span style="flex: 1;">${opt}</span>
          </button>
        `).join('')}
      </div>

      <div style="display: flex; justify-content: flex-end;">
        <button id="btn-submit-answer" disabled
          style="min-height: 52px; padding: 12px 32px; background: #94A3B8; color: #fff; border: none; border-radius: 12px; font-size: 18px; font-weight: 700; cursor: not-allowed; transition: all 0.2s;">
          Jawab &rarr;
        </button>
      </div>
    `;

    // Maskot di layar tes: netral dan tenang, tanpa reaksi benar/salah
    const mascotHost = container.querySelector('#mascot-test-host');
    if (mascotHost) {
      if (mascotInstance) mascotInstance.destroy();
      mascotInstance = createMascot(mascotHost, {
        expression: 'senang',
        pose: 'turun',
        kebersihan: 100,
      }, { size: 120, animated: true, track: false, enableClick: false });
    }

    let selectedOption = null;
    const optButtons = content.querySelectorAll('.option-btn');
    const submitBtn = content.querySelector('#btn-submit-answer');

    optButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        selectedOption = parseInt(btn.dataset.index, 10);
        optButtons.forEach(b => {
          b.style.borderColor = '#E2E8F0';
          b.style.background = '#FFFFFF';
        });
        btn.style.borderColor = 'var(--color-sky)';
        btn.style.background = '#E8F6FB';

        submitBtn.disabled = false;
        submitBtn.style.background = 'var(--color-sky-dark)';
        submitBtn.style.cursor = 'pointer';
      });
    });

    submitBtn.addEventListener('click', async () => {
      if (selectedOption === null) return;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Menyimpan...';

      const elapsedMs = Date.now() - questionStartTime;

      try {
        await api.post(`/api/test/${jenis}/answer`, {
          test_item_id: q.id,
          pilihan: selectedOption,
          ms: elapsedMs,
          opsi_urutan: q.opsi_urutan,
        });

        // Langsung maju ke soal berikutnya tanpa membocorkan benar/salah
        currentIndex++;
        renderQuestion();
      } catch (err) {
        alert(err.message || 'Gagal menyimpan jawaban. Coba lagi.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Jawab &rarr;';
      }
    });
  }

  async function renderCompleted() {
    if (mascotInstance) mascotInstance.destroy();

    try {
      await api.post(`/api/test/${jenis}/finish`, {});
    } catch (e) {
      // Abaikan jika sudah di-finish sebelumnya
    }

    const content = container.querySelector('#test-content');
    content.innerHTML = `
      <div style="text-align: center; padding: 24px 16px;">
        <div id="mascot-finish-host" style="display: flex; justify-content: center; height: 180px; margin-bottom: 16px;"></div>

        <h2 style="color: var(--color-title); font-size: 28px; font-weight: 800; margin: 0 0 8px 0;">Luar Biasa!</h2>
        <p style="color: #475569; font-size: 18px; margin: 0 0 20px 0;">
          Kamu telah menyelesaikan ${jenisTitle} dengan sangat hebat!
        </p>

        <!-- Tampilan Bintang (Bukan angka) -->
        <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 28px;">
          <span style="font-size: 48px; color: #FFD65A;">⭐</span>
          <span style="font-size: 48px; color: #FFD65A;">⭐</span>
          <span style="font-size: 48px; color: #FFD65A;">⭐</span>
        </div>

        <button id="btn-next-action"
          style="min-height: 56px; padding: 14px 36px; background: #059669; color: #ffffff; border: none; border-radius: 14px; font-size: 20px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
          ${jenis === 'pre' ? 'Masuk ke Rumah Dento &rarr;' : 'Isi Kuesioner Kepuasan &rarr;'}
        </button>
      </div>
    `;

    const mascotBox = content.querySelector('#mascot-finish-host');
    mascotInstance = createMascot(mascotBox, {
      expression: 'semangat',
      pose: 'atas',
      kebersihan: 100,
    }, { size: 180, track: true });
    mascotInstance.react('pop');

    content.querySelector('#btn-next-action').addEventListener('click', () => {
      if (jenis === 'pre') {
        router.navigate('home');
      } else {
        router.navigate('survey');
      }
    });
  }

  renderQuestion();

  return {
    destroy() {
      if (mascotInstance) mascotInstance.destroy();
    },
  };
}
