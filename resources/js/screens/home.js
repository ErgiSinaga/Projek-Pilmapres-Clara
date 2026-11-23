import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { ACCESSORIES } from '../mascot/accessories.js';
import { sound } from '../audio.js';

export async function createHomeScreen(container, router) {
  let homeData = null;
  let mascotInstance = null;

  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #E8F6FB;">
      <p style="font-size: 20px; color: var(--color-navy); font-weight: 700;">Memasuki Rumah Dento...</p>
    </div>
  `;

  try {
    homeData = await api.get('/api/home');
  } catch (err) {
    if (err.status === 401) {
      router.navigate('login');
    } else {
      container.innerHTML = `
        <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #E8F6FB; padding: 20px; text-align: center;">
          <h2 style="color: #EF4444; font-size: 24px; margin-bottom: 12px;">Terjadi Kendala</h2>
          <p style="color: #475569; font-size: 16px; margin-bottom: 20px;">${err.message || 'Gagal memuat Rumah Dento.'}</p>
          <button id="btn-retry-home" style="padding: 12px 24px; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Coba Lagi</button>
        </div>
      `;
      container.querySelector('#btn-retry-home')?.addEventListener('click', () => createHomeScreen(container, router));
    }
    return {};
  }

  // Pre-test wajib sebelum akses Rumah Dento (SPEC A5)
  if (!homeData.pre_test || !homeData.pre_test.finished) {
    if (!homeData.student.pet_name) {
      router.navigate('onboarding');
    } else {
      router.navigate('test?jenis=pre');
    }
    return {};
  }

  const pet = homeData.pet || { kebersihan: 100, kebahagiaan: 100, koin: 0, level: 1 };
  const equipped = homeData.equipped || {};
  const bgSvg = ACCESSORIES.latar[equipped.latar] || ACCESSORIES.latar.ruangan;

  // Tentukan ekspresi dan pose Dento berdasarkan kebersihan (SPEC Lampiran B)
  let mascotExpr = 'senang';
  let mascotPose = 'sikat';
  let bubbleText = `Halo sahabatku! Gigi yang bersih membuat senyum kita bersinar. Mau beraktivitas apa hari ini?`;

  if (pet.kebersihan < 40) {
    mascotExpr = 'sedih';
    mascotPose = 'turun';
    bubbleText = `Gigiku ada bercak nih... Yuk sikat gigiku sekarang agar kembali bersih dan berkilau!`;
  } else if (pet.kebersihan < 70) {
    mascotExpr = 'senang';
    mascotPose = 'turun';
    bubbleText = `Plak gigi mulai menempel tipis. Jangan lupa sikat gigi dua kali sehari ya!`;
  }

  if (homeData.post_test && homeData.post_test.unlocked && !homeData.post_test.finished) {
    bubbleText = `🎉 Hebat sekali! Undangan Post-Test sudah terbuka untukmu. Yuk selesaikan sekarang!`;
  }

  container.innerHTML = `
    <div style="position: relative; width: 100%; min-height: 100vh; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">

      <!-- Latar Belakang Kamar/Taman/Langit SVG -->
      <div style="position: absolute; inset: 0; z-index: 1; pointer-events: none;">
        ${bgSvg}
      </div>

      <!-- Bilah Status Atas -->
      <header style="position: relative; z-index: 10; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); border-bottom: 2px solid rgba(74, 168, 216, 0.2); box-shadow: 0 4px 16px rgba(0,0,0,0.03);">

        <!-- Profil & Nama -->
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="background: var(--color-sky-light); border: 2px solid var(--color-sky); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--color-title); font-size: 16px;">
            ${homeData.student.kode.split('-')[1] || '01'}
          </div>
          <div>
            <div style="font-size: 18px; font-weight: 800; color: var(--color-navy); line-height: 1.2;">${homeData.student.pet_name || 'Dento'}</div>
            <div style="font-size: 12px; font-weight: 600; color: #64748B;">Kode: ${homeData.student.kode}</div>
          </div>
        </div>

        <!-- Meter Kebersihan & Kebahagiaan -->
        <div style="display: flex; gap: 20px; align-items: center;">
          <!-- Kebersihan -->
          <div style="display: flex; align-items: center; gap: 8px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2F7FAE" stroke-width="2.5"><path d="M12 2C8 2 5 5 5 9C5 12 7 14 7 18C7 20 8 22 10 22C11 22 11 20 12 20C13 20 13 22 14 22C16 22 17 20 17 18C17 14 19 12 19 9C19 5 16 2 12 2Z"/></svg>
            <div style="width: 100px; height: 14px; background: #E2E8F0; border-radius: 10px; overflow: hidden;">
              <div style="width: ${pet.kebersihan}%; height: 100%; background: ${pet.kebersihan < 40 ? '#EF4444' : (pet.kebersihan < 70 ? '#F59E0B' : '#4AA8D8')}; transition: width 0.3s;"></div>
            </div>
            <span style="font-size: 13px; font-weight: 700; color: var(--color-navy); width: 34px;">${pet.kebersihan}%</span>
          </div>

          <!-- Kebahagiaan -->
          <div style="display: flex; align-items: center; gap: 8px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E85D5D" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <div style="width: 100px; height: 14px; background: #E2E8F0; border-radius: 10px; overflow: hidden;">
              <div style="width: ${pet.kebahagiaan}%; height: 100%; background: #E85D5D; transition: width 0.3s;"></div>
            </div>
            <span style="font-size: 13px; font-weight: 700; color: var(--color-navy); width: 34px;">${pet.kebahagiaan}%</span>
          </div>
        </div>

        <!-- Koin, Level & Kontrol -->
        <div style="display: flex; align-items: center; gap: 14px;">
          <!-- Koin -->
          <div style="display: flex; align-items: center; gap: 6px; background: #FFFBEB; border: 1.5px solid #FCD34D; padding: 6px 14px; border-radius: 20px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#FFD65A" stroke="#D9A400" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>
            <span style="font-size: 16px; font-weight: 800; color: #B45309;">${pet.koin}</span>
          </div>

          <!-- Level -->
          <div style="background: var(--color-sky-dark); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 800;">
            Level ${pet.level}
          </div>

          <!-- Tombol Audio Mute -->
          <button id="btn-toggle-sound" style="background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 50%; width: 38px; height: 38px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <span id="sound-icon" style="font-size: 16px;">${sound.isMuted() ? '🔇' : '🔊'}</span>
          </button>

          <!-- Keluar -->
          <button id="btn-logout" title="Keluar" style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #DC2626; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 700; cursor: pointer;">
            Keluar
          </button>
        </div>
      </header>

      <!-- Area Tengah: Gelembung Bicara & Dento Hero -->
      <main style="position: relative; z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 20px 0;">

        <!-- Gelembung Bicara -->
        <div style="background: #FFFFFF; border: 2.5px solid var(--color-sky); padding: 14px 22px; border-radius: 20px; box-shadow: 0 8px 24px rgba(37, 67, 92, 0.1); max-width: 440px; margin-bottom: 24px; text-align: center; position: relative;">
          <p style="margin: 0; font-size: 17px; font-weight: 700; color: var(--color-navy); line-height: 1.4;">
            ${bubbleText}
          </p>
          <div style="position: absolute; bottom: -12px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-top: 12px solid var(--color-sky);"></div>
        </div>

        <!-- Tombol Aksi Post-Test Jika Terbuka -->
        ${homeData.post_test && homeData.post_test.unlocked && !homeData.post_test.finished ? `
          <button id="btn-start-post" style="margin-bottom: 20px; min-height: 52px; padding: 12px 28px; background: #2563EB; color: #fff; border: 2px solid #1D4ED8; border-radius: 14px; font-size: 18px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35); animation: bob 2s infinite ease-in-out;">
            ⭐ Mulai Post-Test Sekarang &rarr;
          </button>
        ` : ''}

        <!-- Tombol Sertifikat Jika Post-Test Sudah Selesai -->
        ${homeData.post_test && homeData.post_test.finished ? `
          <button id="btn-view-cert" style="margin-bottom: 20px; min-height: 52px; padding: 12px 28px; background: #D97706; color: #fff; border: 2px solid #B45309; border-radius: 14px; font-size: 17px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 16px rgba(217, 119, 6, 0.35);">
            🏆 Lihat Sertifikat Pahlawan Gigi &rarr;
          </button>
        ` : ''}

        <!-- Maskot Hero Dento -->
        <div id="mascot-home-host" style="width: 380px; height: 380px; cursor: pointer;"></div>
      </main>

      <!-- Dok Menu Bawah (Ikon Bulat SVG Tanpa Emoji) -->
      <nav style="position: relative; z-index: 10; padding: 18px 24px; display: flex; justify-content: center; gap: 24px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-top: 2px solid rgba(74, 168, 216, 0.2);">

        <!-- 1. Makan -->
        <button class="dock-btn" data-route="game/pilah-makanan" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer;">
          <div style="width:60px; height:60px; border-radius:50%; background:#FEF3C7; border:3px solid #F59E0B; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(245,158,11,0.25);">
            <!-- SVG Apel & Sayur -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="#F59E0B"><path d="M18.5 7.5A4.5 4.5 0 0 0 14 3c-1.5 0-2.5 1-2.5 1S10.5 3 9 3a4.5 4.5 0 0 0-4.5 4.5C4.5 13 11.5 20 11.5 20s7-7 7-12.5z"/></svg>
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Makan</span>
        </button>

        <!-- 2. Sikat Gigi -->
        <button class="dock-btn" data-modal="sikat" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer;">
          <div style="width:60px; height:60px; border-radius:50%; background:#E0F2FE; border:3px solid #0284C7; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(2,132,199,0.25);">
            <!-- SVG Sikat Gigi -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0284C7" stroke-width="2.5"><path d="M4 20L18 6M15 3L21 9M17 5L19 7"/></svg>
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Sikat Gigi</span>
        </button>

        <!-- 3. Main -->
        <button class="dock-btn" data-modal="main" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer;">
          <div style="width:60px; height:60px; border-radius:50%; background:#FCE7F3; border:3px solid #DB2777; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(219,39,119,0.25);">
            <!-- SVG Puzzle / Kartu Main -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#DB2777" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Main</span>
        </button>

        <!-- 4. Kelas Dokter Gigi -->
        <button class="dock-btn" data-route="videos" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer;">
          <div style="width:60px; height:60px; border-radius:50%; background:#DCFCE7; border:3px solid #16A34A; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(22,163,74,0.25);">
            <!-- SVG Video Play -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="#16A34A"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Kelas</span>
        </button>

        <!-- 5. Toko Aksesori -->
        <button class="dock-btn" data-route="shop" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer;">
          <div style="width:60px; height:60px; border-radius:50%; background:#EDE9FE; border:3px solid #7C3AED; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(124,58,237,0.25);">
            <!-- SVG Shopping Bag -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Toko</span>
        </button>

        <!-- 6. Pesan Hari Ini -->
        <button class="dock-btn" data-route="daily" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer;">
          <div style="width:60px; height:60px; border-radius:50%; background:#FEF08A; border:3px solid #CA8A04; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(202,138,4,0.25);">
            <!-- SVG Surat Pesan -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#CA8A04" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Pesan</span>
        </button>

        <!-- 7. Jurnal Gigi (Habit Tracker) -->
        <button id="dock-btn-jurnal" style="border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:6px; cursor:pointer; position:relative;">
          <div style="width:60px; height:60px; border-radius:50%; background:#CCFBF1; border:3px solid #0D9488; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(13,148,136,0.25); position:relative;">
            <!-- SVG Buku Jurnal & Senyum Gigi -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M9 10h6"/><path d="M12 7v6"/></svg>
            ${(!homeData.habit || !homeData.habit.today_done) ? `
              <div style="position:absolute; top:-2px; right:-2px; width:16px; height:16px; background:#EF4444; border:2px solid #fff; border-radius:50%; box-shadow:0 0 6px rgba(239,68,68,0.8);"></div>
            ` : ''}
          </div>
          <span style="font-size:14px; font-weight:800; color:var(--color-navy);">Jurnal</span>
        </button>

      </nav>

      <!-- Modal Dialog Pilihan Mini Game (Urutan Sikat / Sikat Bersih & Memory / Kuis) -->
      <div id="game-modal" style="display:none; position:fixed; inset:0; z-index:100; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
        <div style="background:#fff; padding:28px; border-radius:24px; max-width:420px; width:90%; text-align:center; box-shadow:0 12px 32px rgba(0,0,0,0.2);">
          <h2 id="modal-title" style="color:var(--color-title); font-size:24px; margin:0 0 16px 0;">Pilih Aktivitas</h2>
          <div id="modal-options" style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;"></div>
          <button id="modal-close" style="padding:10px 24px; background:#F1F5F9; border:none; border-radius:10px; font-size:15px; font-weight:700; color:#475569; cursor:pointer;">Tutup</button>
        </div>
      </div>

      <!-- Modal Sapaan Check-in Harian Jurnal Gigi (Pemicu Login Pertama) -->
      ${homeData.habit && homeData.habit.prompt_today ? `
        <div id="habit-daily-prompt" style="display:flex; position:fixed; inset:0; z-index:120; background:rgba(0,0,0,0.55); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:20px;">
          <div style="background:#fff; padding:32px 28px; border-radius:28px; max-width:440px; width:100%; text-align:center; box-shadow:0 16px 40px rgba(0,0,0,0.25); border:3px solid #0D9488;">
            <div style="width:72px; height:72px; margin:0 auto 16px auto; background:#CCFBF1; border-radius:50%; display:flex; align-items:center; justify-content:center; border:3px solid #0D9488;">
              <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M9 10h6"/><path d="M12 7v6"/></svg>
            </div>
            <h2 style="color:var(--color-navy); font-size:24px; font-weight:800; margin:0 0 10px 0;">Yuk Cerita Hari Ini!</h2>
            <p style="color:#475569; font-size:15px; line-height:1.5; margin:0 0 24px 0;">
              Yuk cerita tentang kebiasaan gigimu hari ini bersama Dento. Ceritakan dengan jujur ya, tidak ada jawaban yang salah!
            </p>
            <div style="display:flex; flex-direction:column; gap:12px;">
              <button id="btn-prompt-cerita" style="min-height:52px; padding:12px 24px; background:linear-gradient(135deg, #0D9488, #059669); color:#fff; border:none; border-radius:16px; font-size:17px; font-weight:800; cursor:pointer; box-shadow:0 6px 16px rgba(13,148,136,0.35);">
                ✨ Ayo Cerita Sekarang!
              </button>
              <button id="btn-prompt-nanti" style="min-height:46px; padding:10px 24px; background:#F1F5F9; color:#64748B; border:none; border-radius:14px; font-size:15px; font-weight:700; cursor:pointer;">
                Nanti Dulu
              </button>
            </div>
          </div>
        </div>
      ` : ''}
    </div>
  `;

  // Render hero maskot Dento di tengah kamar
  const mascotHost = container.querySelector('#mascot-home-host');
  mascotInstance = createMascot(mascotHost, {
    expression: mascotExpr,
    pose: mascotPose,
    kebersihan: pet.kebersihan,
    equipped: equipped,
  }, { size: 380, animated: true, track: true, sleepAfter: 90000 });

  // Event handlers
  container.querySelector('#btn-toggle-sound').addEventListener('click', () => {
    const isMuted = sound.toggleMute();
    container.querySelector('#sound-icon').textContent = isMuted ? '🔇' : '🔊';
  });

  container.querySelector('#btn-logout').addEventListener('click', async () => {
    if (confirm('Kamu ingin keluar dari Dento?')) {
      await api.post('/api/logout', {});
      router.navigate('login');
    }
  });

  container.querySelector('#btn-start-post')?.addEventListener('click', () => {
    router.navigate('test?jenis=post');
  });

  container.querySelector('#btn-view-cert')?.addEventListener('click', () => {
    router.navigate('certificate');
  });

  // Modal handler untuk pilihan game
  const modal = container.querySelector('#game-modal');
  const modalTitle = container.querySelector('#modal-title');
  const modalOptions = container.querySelector('#modal-options');
  container.querySelector('#modal-close').addEventListener('click', () => {
    modal.style.display = 'none';
  });

  container.querySelectorAll('.dock-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const route = btn.dataset.route;
      const modalType = btn.dataset.modal;

      if (route) {
        router.navigate(route);
      } else if (modalType === 'sikat') {
        modalTitle.textContent = 'Pilih Aktivitas Sikat Gigi';
        modalOptions.innerHTML = `
          <button class="modal-choice-btn" data-target="game/urutan-sikat" style="padding:16px; border-radius:14px; border:2px solid #D8EEF9; background:#F8FAFC; text-align:left; font-size:16px; font-weight:700; color:var(--color-navy); cursor:pointer;">
            🦷 Urutan Menyikat Gigi
            <div style="font-size:12px; font-weight:500; color:#64748B; margin-top:4px;">Urutkan 6 langkah sikat gigi yang benar.</div>
          </button>
          <button class="modal-choice-btn" data-target="game/sikat-bersih" style="padding:16px; border-radius:14px; border:2px solid #D8EEF9; background:#F8FAFC; text-align:left; font-size:16px; font-weight:700; color:var(--color-navy); cursor:pointer;">
            ✨ Sikat Gigi Bersih
            <div style="font-size:12px; font-weight:500; color:#64748B; margin-top:4px;">Gosok 6 zona gigi dan bersihkan gelembung plak!</div>
          </button>
        `;
        bindModalChoices();
        modal.style.display = 'flex';
      } else if (modalType === 'main') {
        modalTitle.textContent = 'Pilih Permainan Cerdas';
        modalOptions.innerHTML = `
          <button class="modal-choice-btn" data-target="game/memory-fakta" style="padding:16px; border-radius:14px; border:2px solid #D8EEF9; background:#F8FAFC; text-align:left; font-size:16px; font-weight:700; color:var(--color-navy); cursor:pointer;">
            🃏 Kartu Memory Fakta
            <div style="font-size:12px; font-weight:500; color:#64748B; margin-top:4px;">Temukan 6 pasang kartu bergambar tentang gigi sehat.</div>
          </button>
          <button class="modal-choice-btn" data-target="game/kuis-kilat" style="padding:16px; border-radius:14px; border:2px solid #D8EEF9; background:#F8FAFC; text-align:left; font-size:16px; font-weight:700; color:var(--color-navy); cursor:pointer;">
            ⚡ Kuis Kilat Gigi Sehat
            <div style="font-size:12px; font-weight:500; color:#64748B; margin-top:4px;">Tantangan 5 pertanyaan seru dengan penjelasan langsung!</div>
          </button>
        `;
        bindModalChoices();
        modal.style.display = 'flex';
      }
    });
  });

  function bindModalChoices() {
    modalOptions.querySelectorAll('.modal-choice-btn').forEach(b => {
      b.addEventListener('click', () => {
        modal.style.display = 'none';
        router.navigate(b.dataset.target);
      });
    });
  }

  // Event Jurnal Gigi Dock
  container.querySelector('#dock-btn-jurnal')?.addEventListener('click', () => {
    if (homeData.habit && homeData.habit.today_done) {
      router.navigate('jurnal-ringkasan');
    } else {
      router.navigate('jurnal');
    }
  });

  // Event Prompt Harian Jurnal
  container.querySelector('#btn-prompt-cerita')?.addEventListener('click', () => {
    router.navigate('jurnal');
  });

  container.querySelector('#btn-prompt-nanti')?.addEventListener('click', async () => {
    try {
      await api.post('/api/jurnal/lewati');
    } catch (e) {}
    container.querySelector('#habit-daily-prompt')?.remove();
  });

  return {
    destroy() {
      if (mascotInstance) mascotInstance.destroy();
    },
  };
}
