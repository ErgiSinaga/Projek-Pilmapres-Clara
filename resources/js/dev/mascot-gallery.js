import { createMascot, mascotSVG } from '../mascot/mascot.js';
import { EXPR, POSES } from '../mascot/parts.js';

export function initMascotGallery(container) {
  const expressions = Object.keys(EXPR);
  const poses = Object.keys(POSES);

  container.innerHTML = `
    <div style="padding: 24px; max-width: 1200px; margin: 0 auto;">
      <h1 style="color: #1F6E9C; font-size: 28px; margin-bottom: 8px;">Galeri Uji Maskot Dento</h1>
      <p style="color: #25435C; font-size: 18px; margin-bottom: 24px;">Halaman verifikasi visual maskot (Khusus APP_ENV=local)</p>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 40px; background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <div>
          <h2 style="color: #1F6E9C; font-size: 20px; margin-bottom: 16px;">Hero Interaktif</h2>
          <div id="mascot-hero-container" style="display: flex; justify-content: center; align-items: center; min-height: 420px; background: #E8F6FB; border-radius: 16px;"></div>
        </div>
        <div>
          <h2 style="color: #1F6E9C; font-size: 20px; margin-bottom: 16px;">Kontrol Maskot</h2>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <label style="font-size: 16px; font-weight: 600;">Ekspresi:
              <select id="ctrl-expr" style="display: block; width: 100%; padding: 8px; border-radius: 8px; margin-top: 4px;">
                ${expressions.map(e => `<option value="${e}">${e}</option>`).join('')}
              </select>
            </label>
            <label style="font-size: 16px; font-weight: 600;">Pose:
              <select id="ctrl-pose" style="display: block; width: 100%; padding: 8px; border-radius: 8px; margin-top: 4px;">
                ${poses.map(p => `<option value="${p}">${p}</option>`).join('')}
              </select>
            </label>
            <label style="font-size: 16px; font-weight: 600;">Kebersihan (0 - 100): <span id="val-kebersihan">100</span>%
              <input type="range" id="ctrl-clean" min="0" max="100" value="100" style="display: block; width: 100%; margin-top: 4px;">
            </label>
            <label style="font-size: 16px; font-weight: 600;">Topi:
              <select id="ctrl-hat" style="display: block; width: 100%; padding: 8px; border-radius: 8px; margin-top: 4px;">
                <option value="">Cermin Kepala (Default)</option>
                <option value="dokter">Topi Dokter</option>
                <option value="merah">Topi Merah</option>
              </select>
            </label>
            <label style="font-size: 16px; font-weight: 600;">Kacamata:
              <select id="ctrl-glasses" style="display: block; width: 100%; padding: 8px; border-radius: 8px; margin-top: 4px;">
                <option value="">Tanpa Kacamata</option>
                <option value="bulat">Kacamata Bulat</option>
                <option value="bintang">Kacamata Bintang</option>
              </select>
            </label>
            <label style="font-size: 16px; font-weight: 600;">Warna Jubah:
              <select id="ctrl-cape" style="display: block; width: 100%; padding: 8px; border-radius: 8px; margin-top: 4px;">
                <option value="merah">Merah (Default)</option>
                <option value="biru">Biru</option>
                <option value="hijau">Hijau</option>
              </select>
            </label>
            <div style="display: flex; gap: 8px; margin-top: 8px;">
              <button id="btn-jump" style="flex: 1; padding: 10px; border-radius: 8px; background: #4AA8D8; color: #fff; border: none; cursor: pointer; font-weight: 600;">Jump</button>
              <button id="btn-wobble" style="flex: 1; padding: 10px; border-radius: 8px; background: #FFB3C1; color: #25435C; border: none; cursor: pointer; font-weight: 600;">Wobble</button>
              <button id="btn-pop" style="flex: 1; padding: 10px; border-radius: 8px; background: #FFD65A; color: #25435C; border: none; cursor: pointer; font-weight: 600;">Pop</button>
              <button id="btn-speak" style="flex: 1; padding: 10px; border-radius: 8px; background: #2F7FAE; color: #fff; border: none; cursor: pointer; font-weight: 600;">Bicara</button>
            </div>
          </div>
        </div>
      </div>

      <h2 style="color: #1F6E9C; font-size: 22px; margin-bottom: 16px;">Grid Statis Kombinasi (Ekspresi × Pose)</h2>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px;">
        ${expressions.flatMap(expr =>
          poses.map(pos => `
            <div style="background: #fff; padding: 8px; border-radius: 12px; text-align: center; border: 1px solid #D8EEF9;">
              <div style="font-size: 12px; color: #25435C; font-weight: 600; margin-bottom: 4px;">${expr} / ${pos}</div>
              <div style="display: flex; justify-content: center;">
                ${mascotSVG({ expression: expr, pose: pos, kebersihan: 100 }, { animated: false, size: 100 })}
              </div>
            </div>
          `)
        ).join('')}
      </div>
    </div>
  `;

  const heroHost = container.querySelector('#mascot-hero-container');
  const hero = createMascot(heroHost, {
    expression: 'senang',
    pose: 'sikat',
    kebersihan: 100,
    equipped: {},
  }, { animated: true, size: 360, track: true, sleepAfter: 90000 });

  // Event handlers
  const exprEl = container.querySelector('#ctrl-expr');
  const poseEl = container.querySelector('#ctrl-pose');
  const cleanEl = container.querySelector('#ctrl-clean');
  const cleanValEl = container.querySelector('#val-kebersihan');
  const hatEl = container.querySelector('#ctrl-hat');
  const glassesEl = container.querySelector('#ctrl-glasses');
  const capeEl = container.querySelector('#ctrl-cape');

  function updateHero() {
    hero.set({
      expression: exprEl.value,
      pose: poseEl.value,
      kebersihan: parseInt(cleanEl.value, 10),
      equipped: {
        topi: hatEl.value || undefined,
        kacamata: glassesEl.value || undefined,
        jubah: capeEl.value || 'merah',
      },
    });
  }

  exprEl.addEventListener('change', updateHero);
  poseEl.addEventListener('change', updateHero);
  cleanEl.addEventListener('input', () => {
    cleanValEl.textContent = cleanEl.value;
    updateHero();
  });
  hatEl.addEventListener('change', updateHero);
  glassesEl.addEventListener('change', updateHero);
  capeEl.addEventListener('change', updateHero);

  container.querySelector('#btn-jump').addEventListener('click', () => hero.react('jump'));
  container.querySelector('#btn-wobble').addEventListener('click', () => hero.react('wobble'));
  container.querySelector('#btn-pop').addEventListener('click', () => hero.react('pop'));

  let speaking = false;
  const speakBtn = container.querySelector('#btn-speak');
  speakBtn.addEventListener('click', () => {
    speaking = !speaking;
    hero.speak(speaking);
    speakBtn.textContent = speaking ? 'Stop Bicara' : 'Bicara';
  });

  return {
    destroy() {
      hero.destroy();
    },
  };
}
