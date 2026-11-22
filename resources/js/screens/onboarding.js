import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { store } from '../store.js';

export function createOnboardingScreen(container, router) {
  let step = 1;
  let selectedName = 'Dento';
  let mascotInstance = null;
  const presetNames = ['Dento', 'Gigi Hebat', 'Si Putih', 'Pahlawan Cilik', 'Bintang Senyum'];

  function render() {
    container.innerHTML = `
      <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box;">
        <div style="background: #ffffff; padding: 36px 32px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); max-width: 560px; width: 100%; text-align: center; border: 2px solid #E8F6FB;">

          <div id="mascot-box" style="display: flex; justify-content: center; margin-bottom: 12px; height: 180px;"></div>

          <div id="step-content"></div>

        </div>
      </div>
    `;

    const mascotBox = container.querySelector('#mascot-box');
    mascotInstance = createMascot(mascotBox, {
      expression: 'senang',
      pose: 'lambai',
      kebersihan: 100,
    }, { size: 180, track: true });

    renderStep();
  }

  function renderStep() {
    const content = container.querySelector('#step-content');

    if (step === 1) {
      mascotInstance.set({ expression: 'senang', pose: 'lambai' });
      content.innerHTML = `
        <h2 style="color: var(--color-title); font-size: 26px; font-weight: 800; margin: 0 0 8px 0;">Beri Nama Sahabat Gigimu</h2>
        <p style="color: #64748B; font-size: 16px; margin: 0 0 24px 0;">Pilih salah satu nama pahlawan di bawah ini:</p>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
          ${presetNames.map(name => `
            <button class="name-btn" data-name="${name}"
              style="min-height: 52px; padding: 12px; border-radius: 12px; border: 2px solid ${name === selectedName ? 'var(--color-sky)' : '#E2E8F0'}; background: ${name === selectedName ? '#E8F6FB' : '#FFFFFF'}; color: var(--color-navy); font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
              ${name}
            </button>
          `).join('')}
        </div>

        <button id="btn-next-1"
          style="min-height: 56px; width: 100%; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 14px; font-size: 18px; font-weight: 700; cursor: pointer;">
          Lanjut &rarr;
        </button>
      `;

      content.querySelectorAll('.name-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          selectedName = btn.dataset.name;
          renderStep();
        });
      });

      content.querySelector('#btn-next-1').addEventListener('click', async () => {
        try {
          await api.post('/api/pet/name', { pet_name: selectedName });
          const cur = store.getState().student || {};
          store.setState({ student: { ...cur, pet_name: selectedName } });
          step = 2;
          renderStep();
        } catch (e) {
          alert('Gagal menyimpan nama. Coba lagi ya!');
        }
      });
    } else if (step === 2) {
      mascotInstance.set({ expression: 'semangat', pose: 'atas' });
      mascotInstance.react('jump');
      content.innerHTML = `
        <h2 style="color: var(--color-title); font-size: 26px; font-weight: 800; margin: 0 0 8px 0;">Senang Bertemu Denganmu!</h2>
        <div style="background: #E8F6FB; padding: 18px; border-radius: 16px; margin: 20px 0; color: var(--color-navy); font-size: 17px; line-height: 1.5; text-align: left; border-left: 4px solid var(--color-sky);">
          "Halo! Mulai hari ini, namaku <strong>${selectedName}</strong>! Aku adalah pahlawan gigi yang akan menemanimu belajar menjaga kesehatan gigi. Ayo rawat aku dan kumpulkan koin kesehatan!"
        </div>

        <button id="btn-next-2"
          style="min-height: 56px; width: 100%; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 14px; font-size: 18px; font-weight: 700; cursor: pointer;">
          Lihat Cara Bermain &rarr;
        </button>
      `;

      content.querySelector('#btn-next-2').addEventListener('click', () => {
        step = 3;
        renderStep();
      });
    } else if (step === 3) {
      mascotInstance.set({ expression: 'senang', pose: 'sikat' });
      content.innerHTML = `
        <h2 style="color: var(--color-title); font-size: 26px; font-weight: 800; margin: 0 0 8px 0;">3 Langkah Merawat ${selectedName}</h2>
        <div style="display: flex; flex-direction: column; gap: 12px; margin: 20px 0; text-align: left;">
          <div style="display: flex; gap: 12px; align-items: center; background: #F8FAFC; padding: 12px 16px; border-radius: 12px;">
            <div style="font-size: 24px; font-weight: 800; color: var(--color-sky); width: 32px;">1</div>
            <div style="font-size: 15px; color: #334155;"><strong>Beri Makan Sehat</strong>: Pilih makanan baik yang membuat gigi senang dan hindari makanan manis lengket.</div>
          </div>
          <div style="display: flex; gap: 12px; align-items: center; background: #F8FAFC; padding: 12px 16px; border-radius: 12px;">
            <div style="font-size: 24px; font-weight: 800; color: var(--color-sky); width: 32px;">2</div>
            <div style="font-size: 15px; color: #334155;"><strong>Sikat Gigi Teratur</strong>: Pelajari urutan menyikat gigi yang tepat dan bersihkan plak gigi.</div>
          </div>
          <div style="display: flex; gap: 12px; align-items: center; background: #F8FAFC; padding: 12px 16px; border-radius: 12px;">
            <div style="font-size: 24px; font-weight: 800; color: var(--color-sky); width: 32px;">3</div>
            <div style="font-size: 15px; color: #334155;"><strong>Kelas Dokter Gigi</strong>: Tonton video seru dan jawab tantangannya untuk kumpulkan koin!</div>
          </div>
        </div>

        <div style="background: #FFFBEB; border: 1px solid #FDE68A; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; color: #92400E;">
          📝 Sebelum masuk ke Rumah ${selectedName}, ayo ikuti Pre-Test singkat terlebih dahulu.
        </div>

        <button id="btn-start-pre"
          style="min-height: 56px; width: 100%; background: #059669; color: #fff; border: none; border-radius: 14px; font-size: 20px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
          Mulai Pre-Test Sekarang
        </button>
      `;

      content.querySelector('#btn-start-pre').addEventListener('click', () => {
        router.navigate('test?jenis=pre');
      });
    }
  }

  render();

  return {
    destroy() {
      if (mascotInstance) mascotInstance.destroy();
    },
  };
}
