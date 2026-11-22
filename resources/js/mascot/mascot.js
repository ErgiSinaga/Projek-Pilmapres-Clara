import { C, CAPES, EXPR, POSES, MOUTH_CLOSED, SPARKLES_HERO, cape, SHOES, body, overlay, CLASPS, BADGE, HEADGEAR } from './parts.js';
import { ACCESSORIES } from './accessories.js';   // topi, kacamata (SVG buatan sendiri, lihat A.7)
import './mascot.css';

const LABEL = { senang: 'senang', semangat: 'bersemangat', bingung: 'bingung', sedih: 'sedih', makan: 'sedang makan', tidur: 'tidur', bersih: 'bersih berkilau' };
const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
let uid = 0;

export function mascotSVG(state = {}, { animated = true, size = 400 } = {}) {
  const { expression = 'senang', pose = 'sikat', kebersihan = 100, equipped = {} } = state;
  const e = EXPR[expression] ?? EXPR.senang;
  const p = POSES[pose] ?? POSES.turun;
  const id = ++uid;
  const hat = equipped.topi ? (ACCESSORIES.topi[equipped.topi] ?? HEADGEAR) : HEADGEAR;
  const glasses = equipped.kacamata ? (ACCESSORIES.kacamata[equipped.kacamata] ?? '') : '';
  return `<svg class="mascot${animated ? '' : ' is-static'}" viewBox="-30 -28 260 300" width="${size}" height="${Math.round(size * 300 / 260)}" role="img" aria-label="Dento, pahlawan gigi sehat, ${LABEL[expression] ?? 'senang'}">` +
    `<ellipse cx="100" cy="236" rx="62" ry="8" fill="${C.navy}" opacity=".12"/>` +
    `<g class="m-a m-react"><g class="m-a m-bob">` +
      cape(equipped.jubah ?? 'merah') + SHOES + body(id) + overlay(kebersihan) + CLASPS + BADGE + hat +
      (e.brows ?? '') +
      `<g class="m-look"><g class="m-a m-blink">${e.eyes}</g></g>` + glasses +
      e.cheeks + `<g class="m-mouth">${e.mouth}</g>` + (e.extra ?? '') +
      p.l + p.r +
    `</g></g>` +
    (animated && expression !== 'bersih' ? SPARKLES_HERO : '') +
    `</svg>`;
}

// Komponen hidup. host = elemen kontainer. Hanya SATU instance beranimasi per layar; instance kecil pakai animated:false.
export function createMascot(host, initial = {}, { animated = true, size = 400, track = true, sleepAfter = 0, enableClick = true } = {}) {
  let state = { expression: 'senang', pose: 'sikat', kebersihan: 100, equipped: {}, ...initial };
  let last = '', speakTimer = null, idleTimer = null, raf = 0;

  const render = () => {
    const key = JSON.stringify(state);
    if (key === last) return;           // jangan render ulang jika tidak berubah (animasi tidak restart)
    last = key;
    host.innerHTML = mascotSVG(state, { animated, size });
  };

  const set = (patch) => { state = { ...state, ...patch }; render(); };

  // Reaksi sesaat: 'jump' (jawaban benar), 'wobble' (salah, lembut), 'pop' (hadiah)
  const react = (type) => {
    const g = host.querySelector('.m-react'); if (!g || reduced()) return;
    g.classList.remove('react-jump', 'react-wobble', 'react-pop');
    void g.getBoundingClientRect();
    g.classList.add(`react-${type}`);
    g.addEventListener('animationend', () => g.classList.remove(`react-${type}`), { once: true });
  };

  // Berbicara: mulut membuka-menutup selama teks diketik atau audio diputar
  const speak = (on) => {
    clearInterval(speakTimer);
    const mouth = () => host.querySelector('.m-mouth');
    if (!on) { if (mouth()) mouth().innerHTML = EXPR[state.expression]?.mouth || ''; return; }
    let open = true;
    speakTimer = setInterval(() => {
      if (mouth()) mouth().innerHTML = open ? EXPR.senang.mouth : MOUTH_CLOSED;
      open = !open;
    }, 170);
  };

  // Mata mengikuti kursor (maks 3 unit), dibatasi per frame
  const onMove = (ev) => {
    if (raf || reduced()) return;
    raf = requestAnimationFrame(() => {
      raf = 0;
      const look = host.querySelector('.m-look'); if (!look) return;
      const r = host.getBoundingClientRect();
      const dx = ev.clientX - (r.left + r.width / 2), dy = ev.clientY - (r.top + r.height * 0.3);
      const d = Math.hypot(dx, dy) || 1, k = Math.min(3, d / 60);
      look.style.transform = `translate(${(dx / d) * k}px, ${(dy / d) * k}px)`;
    });
  };

  // Tidur otomatis jika lama tidak ada interaksi (hanya di hub), bangun saat disentuh
  const resetIdle = () => {
    if (!sleepAfter) return;
    clearTimeout(idleTimer);
    if (state._autoSleep) set({ expression: 'senang', pose: 'sikat', _autoSleep: false });
    idleTimer = setTimeout(() => set({ expression: 'tidur', pose: 'turun', _autoSleep: true }), sleepAfter);
  };

  // Klik pada maskot: tertawa kecil
  const onClick = () => {
    if (!enableClick) return;
    const prev = { expression: state.expression, pose: state.pose };
    set({ expression: 'semangat', pose: 'atas' });
    react('jump');
    setTimeout(() => set(prev), 900);
  };

  render();
  if (animated && track) addEventListener('pointermove', onMove, { passive: true });
  if (sleepAfter) { ['pointerdown', 'keydown'].forEach((t) => addEventListener(t, resetIdle)); resetIdle(); }
  if (enableClick) host.addEventListener('click', onClick);

  return {
    set, react, speak,
    destroy() {
      clearInterval(speakTimer); clearTimeout(idleTimer); cancelAnimationFrame(raf);
      removeEventListener('pointermove', onMove);
      ['pointerdown', 'keydown'].forEach((t) => removeEventListener(t, resetIdle));
      host.removeEventListener('click', onClick); host.innerHTML = '';
    },
  };
}
