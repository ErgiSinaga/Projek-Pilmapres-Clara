import { C } from './parts.js';

export const ACCESSORIES = {
  topi: {
    // Topi dokter: topi perawat/dokter putih dengan pita biru langit dan tanda plus merah
    dokter: `
      <g id="acc-topi-dokter">
        <path d="M48 24C48 6 65 -4 100 -4C135 -4 152 6 152 24L155 36C155 40 152 44 146 44L54 44C48 44 45 40 45 36Z" fill="#FFFFFF" stroke="${C.navy}" stroke-width="3" stroke-linejoin="round"/>
        <path d="M48 34H152" stroke="${C.sky}" stroke-width="6"/>
        <rect x="96" y="8" width="8" height="20" rx="2" fill="#E85D5D"/>
        <rect x="90" y="14" width="20" height="8" rx="2" fill="#E85D5D"/>
      </g>
    `,
    // Topi merah: topi pahlawan merah dengan bintang emas di depan
    merah: `
      <g id="acc-topi-merah">
        <path d="M48 24C48 6 65 -6 100 -6C135 -6 152 6 152 24L158 38C158 42 154 46 148 46L52 46C46 46 42 42 42 38Z" fill="#E85D5D" stroke="#B93A3A" stroke-width="3" stroke-linejoin="round"/>
        <polygon points="100,14 102.5,21 110,21.5 104,26.5 106,34 100,30 94,34 96,26.5 90,21.5 97.5,21" fill="${C.gold}" stroke="${C.goldDark}" stroke-width="1.5" stroke-linejoin="round"/>
      </g>
    `,
  },
  kacamata: {
    // Kacamata bulat: lensa lingkaran r=15, pusat di (78, 76) dan (122, 76), jembatan y=76
    bulat: `
      <g id="acc-kacamata-bulat">
        <circle cx="78" cy="76" r="17" fill="rgba(255,255,255,0.25)" stroke="${C.navy}" stroke-width="4"/>
        <circle cx="122" cy="76" r="17" fill="rgba(255,255,255,0.25)" stroke="${C.navy}" stroke-width="4"/>
        <line x1="95" y1="76" x2="105" y2="76" stroke="${C.navy}" stroke-width="4" stroke-linecap="round"/>
        <line x1="61" y1="76" x2="48" y2="72" stroke="${C.navy}" stroke-width="3.5" stroke-linecap="round"/>
        <line x1="139" y1="76" x2="152" y2="72" stroke="${C.navy}" stroke-width="3.5" stroke-linecap="round"/>
        <path d="M72 68A10 10 0 0 1 84 68" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" fill="none" opacity="0.8"/>
        <path d="M116 68A10 10 0 0 1 128 68" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" fill="none" opacity="0.8"/>
      </g>
    `,
    // Kacamata bintang emas
    bintang: `
      <g id="acc-kacamata-bintang">
        <g transform="translate(78 76)">
          <polygon points="0,-17 5,-5 17,-5 8,3 11,15 0,8 -11,15 -8,3 -17,-5 -5,-5" fill="rgba(255,214,90,0.3)" stroke="${C.goldDark}" stroke-width="3" stroke-linejoin="round"/>
        </g>
        <g transform="translate(122 76)">
          <polygon points="0,-17 5,-5 17,-5 8,3 11,15 0,8 -11,15 -8,3 -17,-5 -5,-5" fill="rgba(255,214,90,0.3)" stroke="${C.goldDark}" stroke-width="3" stroke-linejoin="round"/>
        </g>
        <line x1="94" y1="76" x2="106" y2="76" stroke="${C.goldDark}" stroke-width="4" stroke-linecap="round"/>
      </g>
    `,
  },
  latar: {
    ruangan: `
      <svg viewBox="0 0 800 500" class="room-bg" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
        <defs>
          <linearGradient id="wallGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#E8F6FB"/>
            <stop offset="100%" stop-color="#D0EDF8"/>
          </linearGradient>
          <linearGradient id="floorGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#B8E1F3"/>
            <stop offset="100%" stop-color="#9AD3EC"/>
          </linearGradient>
        </defs>
        <!-- Dinding -->
        <rect x="0" y="0" width="800" height="360" fill="url(#wallGrad)"/>
        <!-- Lantai -->
        <rect x="0" y="360" width="800" height="140" fill="url(#floorGrad)"/>
        <line x1="0" y1="360" x2="800" y2="360" stroke="#7CBEDD" stroke-width="4"/>
        <!-- Jendela -->
        <rect x="100" y="60" width="140" height="180" rx="16" fill="#FFFFFF" stroke="#4AA8D8" stroke-width="6"/>
        <rect x="110" y="70" width="120" height="160" rx="10" fill="#E1F4FD"/>
        <line x1="170" y1="70" x2="170" y2="230" stroke="#4AA8D8" stroke-width="4"/>
        <line x1="110" y1="150" x2="230" y2="150" stroke="#4AA8D8" stroke-width="4"/>
        <!-- Rak obat / cermin dokter gigi di dinding -->
        <rect x="580" y="80" width="130" height="100" rx="12" fill="#FFFFFF" stroke="#4AA8D8" stroke-width="5"/>
        <line x1="580" y1="130" x2="710" y2="130" stroke="#4AA8D8" stroke-width="4"/>
        <!-- Botol obat kumur & sikat cadangan -->
        <rect x="595" y="98" width="16" height="28" rx="4" fill="#4AA8D8"/>
        <rect x="620" y="94" width="20" height="32" rx="4" fill="#FFB3C1"/>
        <rect x="655" y="142" width="30" height="20" rx="4" fill="#FFD65A"/>
      </svg>
    `,
    taman: `
      <svg viewBox="0 0 800 500" class="room-bg" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
        <defs>
          <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#CBE9FB"/>
            <stop offset="100%" stop-color="#EAF5FD"/>
          </linearGradient>
          <linearGradient id="grassGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#76D38B"/>
            <stop offset="100%" stop-color="#4FB966"/>
          </linearGradient>
        </defs>
        <rect x="0" y="0" width="800" height="340" fill="url(#skyGrad)"/>
        <!-- Matahari lembut -->
        <circle cx="120" cy="90" r="45" fill="#FFE58F" opacity="0.8"/>
        <!-- Awan -->
        <path d="M240 100 Q260 70 290 85 Q320 60 350 85 Q380 90 380 115 Q360 135 250 130 Z" fill="#FFFFFF" opacity="0.9"/>
        <path d="M520 80 Q540 55 570 70 Q600 50 625 75 Q650 80 650 105 Q630 120 530 115 Z" fill="#FFFFFF" opacity="0.85"/>
        <!-- Bukit & Rumput -->
        <ellipse cx="250" cy="400" rx="380" ry="120" fill="#8CE09F"/>
        <rect x="0" y="340" width="800" height="160" fill="url(#grassGrad)"/>
        <!-- Bunga kecil -->
        <circle cx="150" cy="380" r="8" fill="#FFFDF7"/>
        <circle cx="150" cy="380" r="4" fill="#FFD65A"/>
        <circle cx="680" cy="390" r="8" fill="#FFB3C1"/>
        <circle cx="680" cy="390" r="4" fill="#FFD65A"/>
      </svg>
    `,
    langit: `
      <svg viewBox="0 0 800 500" class="room-bg" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
        <defs>
          <linearGradient id="cosmicSky" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1A3350"/>
            <stop offset="60%" stop-color="#2D5A85"/>
            <stop offset="100%" stop-color="#5599C8"/>
          </linearGradient>
        </defs>
        <rect x="0" y="0" width="800" height="500" fill="url(#cosmicSky)"/>
        <!-- Bintang-bintang -->
        <circle cx="80" cy="70" r="2.5" fill="#FFFDF7" opacity="0.9"/>
        <circle cx="160" cy="130" r="2" fill="#FFD65A" opacity="0.8"/>
        <circle cx="280" cy="50" r="3" fill="#FFFDF7" opacity="0.9"/>
        <circle cx="390" cy="110" r="2" fill="#FFFDF7" opacity="0.8"/>
        <circle cx="500" cy="45" r="2.5" fill="#FFD65A" opacity="0.9"/>
        <circle cx="650" cy="90" r="3" fill="#FFFDF7" opacity="0.95"/>
        <circle cx="720" cy="160" r="2" fill="#FFFDF7" opacity="0.75"/>
        <!-- Bulan sabit ramah -->
        <path d="M680 50 A26 26 0 1 0 710 86 A22 22 0 1 1 680 50 Z" fill="#FFE58F"/>
        <!-- Awan malam lembut di bawah -->
        <path d="M-20 440 Q100 390 240 430 Q380 400 520 430 Q680 390 820 440 L820 500 L-20 500 Z" fill="rgba(255,255,255,0.2)"/>
      </svg>
    `,
  },
};
