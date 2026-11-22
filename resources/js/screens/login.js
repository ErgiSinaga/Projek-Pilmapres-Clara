import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { store } from '../store.js';

export function createLoginScreen(container, router) {
  let mascotInstance = null;

  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
      <div style="background: #ffffff; padding: 36px 32px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); max-width: 440px; width: 100%; text-align: center; border: 2px solid #E8F6FB;">

        <div id="mascot-host" style="display: flex; justify-content: center; margin-bottom: 12px; height: 180px;"></div>

        <h1 style="color: var(--color-title); font-size: 32px; font-weight: 800; margin: 0 0 6px 0;">Rumah Dento</h1>
        <p style="color: #64748B; font-size: 18px; margin: 0 0 24px 0;">Sahabat Pahlawan Gigi Sehat</p>

        <div id="error-message" style="display: none; background: #FEF2F2; color: #991B1B; padding: 12px 16px; border-radius: 12px; font-size: 15px; margin-bottom: 20px; border-left: 4px solid #EF4444; text-align: left; line-height: 1.4;"></div>

        <form id="login-form" style="display: flex; flex-direction: column; gap: 16px;">
          <div style="text-align: left;">
            <label for="input-kode" style="display: block; font-size: 15px; font-weight: 700; color: var(--color-navy); margin-bottom: 6px;">Kode Peserta</label>
            <input type="text" id="input-kode" required placeholder="Contoh: S1-01" autocomplete="off"
              style="width: 100%; padding: 14px 16px; border-radius: 12px; border: 2px solid #D8EEF9; font-size: 18px; font-weight: 700; text-transform: uppercase; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
              onfocus="this.style.borderColor='var(--color-sky)';" onblur="this.style.borderColor='#D8EEF9';">
          </div>

          <div style="text-align: left;">
            <label for="input-pin" style="display: block; font-size: 15px; font-weight: 700; color: var(--color-navy); margin-bottom: 6px;">4 Angka PIN</label>
            <input type="password" id="input-pin" required maxlength="4" pattern="[0-9]*" inputmode="numeric" placeholder="••••"
              style="width: 100%; padding: 14px 16px; border-radius: 12px; border: 2px solid #D8EEF9; font-size: 24px; font-weight: 800; letter-spacing: 6px; text-align: center; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
              onfocus="this.style.borderColor='var(--color-sky)';" onblur="this.style.borderColor='#D8EEF9';">
          </div>

          <button type="submit" id="btn-submit"
            style="min-height: 56px; margin-top: 8px; background: var(--color-sky-dark); color: #ffffff; border: none; border-radius: 14px; font-size: 20px; font-weight: 700; cursor: pointer; transition: transform 0.1s, background-color 0.2s; box-shadow: 0 4px 12px rgba(47, 127, 174, 0.3);">
            Mulai Bermain
          </button>
        </form>

        <div style="margin-top: 24px; font-size: 14px; color: #94A3B8;">
          Belum punya kode? Tanyakan kepada gurumu di kelas ya!
        </div>
      </div>
    </div>
  `;

  const mascotHost = container.querySelector('#mascot-host');
  mascotInstance = createMascot(mascotHost, {
    expression: 'senang',
    pose: 'lambai',
    kebersihan: 100,
  }, { size: 180, track: true });

  const form = container.querySelector('#login-form');
  const kodeInput = container.querySelector('#input-kode');
  const pinInput = container.querySelector('#input-pin');
  const errorBox = container.querySelector('#error-message');
  const submitBtn = container.querySelector('#btn-submit');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorBox.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Memeriksa...';

    try {
      const res = await api.post('/api/login', {
        kode: kodeInput.value.trim(),
        pin: pinInput.value.trim(),
      });

      store.setState({ student: res.student });

      mascotInstance.set({ expression: 'semangat', pose: 'atas' });
      mascotInstance.react('jump');

      setTimeout(() => {
        if (res.student.needs_onboarding) {
          router.navigate('onboarding');
        } else {
          router.navigate('home');
        }
      }, 700);
    } catch (err) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Mulai Bermain';

      mascotInstance.set({ expression: 'bingung', pose: 'tunjuk' });
      mascotInstance.react('wobble');

      errorBox.textContent = err.message || 'Tidak dapat terhubung ke server. Periksa jaringan internet ya.';
      errorBox.style.display = 'block';
    }
  });

  return {
    destroy() {
      if (mascotInstance) mascotInstance.destroy();
    },
  };
}
