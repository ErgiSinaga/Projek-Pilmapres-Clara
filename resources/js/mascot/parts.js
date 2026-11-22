export const C = {
  sky: '#4AA8D8', skyDark: '#2F7FAE', skyLight: '#D8EEF9', tooth: '#FFFDF7', navy: '#25435C',
  cheek: '#FFB3C1', gold: '#FFD65A', goldDark: '#D9A400',
  mirror: '#D9E2EA', mirrorDark: '#7F97AA', mirrorLight: '#F4FAFD',
  mouth: '#C2415D', tongue: '#FF8FA3', tear: '#8FD3F4', cavity: '#8C7B5E', plak: '#E8D77A',
};

export const CAPES = {
  merah: { fill: '#E85D5D', dark: '#B93A3A' },
  biru:  { fill: '#5B8DEF', dark: '#3A63B8' },
  hijau: { fill: '#4FBF8A', dark: '#2E8A5F' },
};

const BODY_D = 'M100 12C70 0 28 8 24 52C21 82 40 98 46 128C52 160 52 205 72 205C90 205 88 170 100 170C112 170 110 205 128 205C148 205 148 160 154 128C160 98 179 82 176 52C172 8 130 0 100 12Z';
const STAR = '100,135 103.8,144.7 114.3,145.4 106.2,152 108.8,162.1 100,156.5 91.2,162.1 93.8,152 85.7,145.4 96.2,144.7';
const SPARK_D = 'M0 -12L3 -3L12 0L3 3L0 12L-3 3L-12 0L-3 -3Z';

const line = (d, color, w) => `<path d="${d}" fill="none" stroke="${color}" stroke-width="${w}" stroke-linecap="round"/>`;

// Bintang berkilau: <g> membawa transform posisi, <path> di dalamnya yang dianimasikan.
export const sparkle = (x, y, s = 1, delay = 0) =>
  `<g transform="translate(${x} ${y}) scale(${s})"><path class="m-a m-tw" style="animation-delay:${delay}s" d="${SPARK_D}" fill="${C.gold}"/></g>`;

export const cape = (k = 'merah') => {
  const { fill, dark } = CAPES[k] ?? CAPES.merah;
  return `<g class="m-a m-cape"><path d="M40 104C14 140 0 196 18 232Q38 222 54 236Q76 224 100 238Q124 224 146 236Q162 222 182 232C200 196 186 140 160 104Z" fill="${fill}" stroke="${dark}" stroke-width="4" stroke-linejoin="round"/>` +
         `<path d="M100 238Q92 190 70 140M100 238Q108 190 130 140" fill="none" stroke="${dark}" stroke-width="3" opacity=".55" stroke-linecap="round"/></g>`;
};

export const SHOES =
  `<ellipse cx="72" cy="208" rx="21" ry="10" fill="${C.sky}" stroke="${C.skyDark}" stroke-width="3"/>` +
  `<ellipse cx="128" cy="208" rx="21" ry="10" fill="${C.sky}" stroke="${C.skyDark}" stroke-width="3"/>` +
  `<path d="M54 213Q72 220 90 213M110 213Q128 220 146 213" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round"/>`;

export const body = (uid) =>
  `<defs><linearGradient id="g${uid}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset=".7" stop-color="#F3F9FD"/><stop offset="1" stop-color="#D6E9F5"/></linearGradient></defs>` +
  `<path d="${BODY_D}" fill="url(#g${uid})" stroke="${C.sky}" stroke-width="6" stroke-linejoin="round"/>` +
  line('M44 58Q46 38 62 30M52 140Q50 160 56 178', C.skyLight, 6);

// Lapisan status kebersihan (di atas badan, di bawah kostum)
export const overlay = (kebersihan) =>
  kebersihan >= 70 ? '' :
  kebersihan >= 40 ? `<ellipse cx="100" cy="44" rx="52" ry="22" fill="${C.plak}" opacity=".35"/>` :
  `<ellipse cx="132" cy="40" rx="13" ry="9" fill="${C.cavity}" opacity=".85"/><circle cx="116" cy="46" r="4" fill="${C.cavity}" opacity=".85"/>`;

export const CLASPS =
  `<circle cx="38" cy="110" r="6" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="2"/>` +
  `<circle cx="162" cy="110" r="6" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="2"/>`;

export const BADGE = `<polygon points="${STAR}" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="2.5" stroke-linejoin="round"/>`;

export const HEADGEAR =
  line('M27 52Q100 10 173 52', C.navy, 7) +
  `<circle cx="100" cy="28" r="16" fill="${C.mirror}" stroke="${C.mirrorDark}" stroke-width="4"/>` +
  `<circle cx="100" cy="28" r="10" fill="${C.mirrorLight}"/>` +
  line('M93 24Q96 19 102 20', '#fff', 3) +
  `<circle cx="100" cy="28" r="3" fill="${C.mirrorDark}"/>`;

// ---------- Wajah ----------
const brow = (d) => line(d, C.navy, 4.5);
const dot = (cx, cy) =>
  `<circle cx="${cx}" cy="${cy}" r="11" fill="${C.navy}"/><circle cx="${cx + 4}" cy="${cy - 5}" r="4" fill="#fff"/><circle cx="${cx - 4}" cy="${cy + 5}" r="2" fill="#fff"/>`;
const arcUp   = (x, y) => line(`M${x - 10} ${y}Q${x} ${y - 14} ${x + 10} ${y}`, C.navy, 5);
const arcDown = (x, y) => line(`M${x - 10} ${y}Q${x} ${y + 8} ${x + 10} ${y}`, C.navy, 5);
const cheeks = (y = 98, rx = 11) =>
  `<ellipse cx="58" cy="${y}" rx="${rx}" ry="7" fill="${C.cheek}"/><ellipse cx="142" cy="${y}" rx="${rx}" ry="7" fill="${C.cheek}"/>`;
const tongue = (d) => `<path d="${d}" fill="${C.tongue}"/>`;

export const MOUTH_CLOSED = line('M84 100Q100 116 116 100', C.navy, 5);

export const EXPR = {
  senang: {
    brows: brow('M66 60Q78 52 90 58M110 58Q122 52 134 60'),
    eyes: dot(78, 76) + dot(122, 76), cheeks: cheeks(98),
    mouth: `<path d="M78 98Q100 128 122 98Z" fill="${C.mouth}"/>` + tongue('M90 108Q100 117 110 108Q100 103 90 108Z'),
  },
  semangat: {
    brows: brow('M66 56Q78 48 90 56M110 56Q122 48 134 56'),
    eyes: arcUp(78, 80) + arcUp(122, 80), cheeks: cheeks(98),
    mouth: `<path d="M74 94Q100 134 126 94Z" fill="${C.mouth}"/>` + tongue('M89 108Q100 118 111 108Q100 102 89 108Z'),
  },
  bingung: {
    brows: brow('M66 54L90 60M110 58L134 48'),
    eyes: dot(78, 78) + dot(122, 78), cheeks: cheeks(100),
    mouth: line('M84 106Q92 98 100 106T116 106', C.navy, 5),
  },
  sedih: {
    brows: brow('M66 66L90 56M110 56L134 66'),
    eyes: dot(78, 80) + dot(122, 80), cheeks: cheeks(102),
    mouth: line('M86 114Q100 100 114 114', C.navy, 5),
    extra: `<path d="M68 92Q63 102 68 106Q73 102 68 92Z" fill="${C.tear}"/>`,
  },
  makan: {
    brows: brow('M66 60Q78 52 90 58M110 58Q122 52 134 60'),
    eyes: arcUp(78, 80) + arcUp(122, 80), cheeks: cheeks(100, 13),
    mouth: `<g class="m-a m-chew"><ellipse cx="100" cy="108" rx="13" ry="10" fill="${C.mouth}"/><ellipse cx="100" cy="112" rx="7" ry="4" fill="${C.tongue}"/></g>`,
  },
  tidur: {
    brows: '',
    eyes: arcDown(78, 78) + arcDown(122, 78), cheeks: cheeks(98),
    mouth: line('M92 106Q100 111 108 106', C.navy, 4),
    extra: `<path class="m-zz" d="M150 40h14l-14 14h14M170 22h10l-10 10h10" fill="none" stroke="${C.navy}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>`,
  },
  bersih: {
    brows: brow('M66 60Q78 52 90 58M110 58Q122 52 134 60'),
    eyes: arcUp(78, 80) + arcUp(122, 80), cheeks: cheeks(98),
    mouth: line('M82 98Q100 120 118 98', C.navy, 5),
    extra: sparkle(152, 58, .7, 0) + sparkle(50, 46, .6, .5) + sparkle(160, 126, .5, 1),
  },
};

// ---------- Pose tangan ----------
const armPath = (d) => line(d, C.sky, 14) + line(d, C.tooth, 8);
const hand = (x, y) => `<circle cx="${x}" cy="${y}" r="9" fill="${C.tooth}" stroke="${C.sky}" stroke-width="4"/>`;
const arm = (d, x, y) => armPath(d) + hand(x, y);

const L_DOWN = arm('M44 124Q30 150 36 168', 37, 172);
const R_DOWN = arm('M156 124Q170 150 164 168', 163, 172);
const L_WAVE = `<g class="m-a m-wave">${arm('M44 124Q22 120 12 98', 11, 93)}` +
  line('M-6 80Q-13 90 -6 100M-14 76Q-23 90 -14 104', C.sky, 3).replace('<path ', '<path opacity=".6" ') + `</g>`;

const BRUSH =
  line('M188 98L208 42', C.navy, 12) + line('M188 98L208 42', C.sky, 7) +
  `<g transform="translate(210 36) rotate(20)"><rect x="-9" y="-30" width="18" height="30" rx="6" fill="${C.tooth}" stroke="${C.navy}" stroke-width="3"/>` +
  `<rect x="-6" y="-27" width="12" height="13" rx="3" fill="${C.cheek}"/>` +
  `<path d="M-3 -26V-16M3 -26V-16" stroke="#fff" stroke-width="2" stroke-linecap="round"/></g>` +
  `<circle cx="224" cy="22" r="6" fill="#fff" stroke="${C.tear}" stroke-width="2"/>` +
  `<circle cx="216" cy="6" r="4" fill="#fff" stroke="${C.tear}" stroke-width="2"/>` +
  `<circle cx="230" cy="42" r="4" fill="#fff" stroke="${C.tear}" stroke-width="2"/>`;

export const POSES = {
  turun:  { l: L_DOWN, r: R_DOWN },
  lambai: { l: L_WAVE, r: R_DOWN },
  atas:   { l: arm('M44 124Q22 106 20 80', 20, 75), r: arm('M156 124Q178 106 180 80', 180, 75) },
  tunjuk: { l: L_DOWN, r: arm('M156 124Q180 108 178 74', 178, 69) },
  sikat:  { l: L_WAVE, r: armPath('M156 124Q176 122 186 102') + BRUSH + hand(188, 98) },
};

export const SPARKLES_HERO = sparkle(4, 30, 1, 0) + sparkle(196, -6, .8, .6) + sparkle(-4, 176, .7, 1.1);
