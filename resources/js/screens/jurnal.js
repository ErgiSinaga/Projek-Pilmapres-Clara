import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createJurnalScreen(container, router) {
  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #E8F6FB;">
      <p style="font-size: 20px; color: var(--color-navy); font-weight: 700;">Membuka Jurnal Gigi Dento...</p>
    </div>
  `;

  let jurnalData = null;
  try {
    jurnalData = await api.get('/api/jurnal/hari-ini');
  } catch (err) {
    if (err.status === 403) {
      container.innerHTML = `
        <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #E8F6FB; padding: 24px; text-align: center;">
          <h2 style="color: var(--color-navy); font-size: 24px; margin-bottom: 12px;">Pre-Test Belum Selesai</h2>
          <p style="color: #475569; font-size: 16px; margin-bottom: 24px; max-width: 400px;">Yuk selesaikan Pre-Test terlebih dahulu sebelum mengisi Jurnal Gigi bersama Dento!</p>
          <button id="btn-to-pretest" style="min-height: 52px; padding: 12px 28px; background: var(--color-sky-dark); color: #fff; border: none; border-radius: 14px; font-size: 16px; font-weight: 800; cursor: pointer;">Mulai Pre-Test &rarr;</button>
        </div>
      `;
      container.querySelector('#btn-to-pretest')?.addEventListener('click', () => router.navigate('test?jenis=pre'));
      return {};
    }

    container.innerHTML = `
      <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #E8F6FB; padding: 24px; text-align: center;">
        <h2 style="color: #EF4444; font-size: 22px; margin-bottom: 12px;">Terjadi Kendala</h2>
        <p style="color: #475569; font-size: 16px; margin-bottom: 20px;">${err.message || 'Gagal memuat Jurnal Gigi.'}</p>
        <button id="btn-back-home" style="padding: 12px 24px; background: var(--color-navy); color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Kembali ke Beranda</button>
      </div>
    `;
    container.querySelector('#btn-back-home')?.addEventListener('click', () => router.navigate('home'));
    return {};
  }

  // Jika sudah selesai hari ini, langsung ke layar terima kasih / ringkasan
  if (jurnalData.selesai) {
    renderCompletionScreen(container, router, null, jurnalData.rekomendasi, true);
    return {};
  }

  let items = jurnalData.items || [];
  let currentIndex = 0;
  let startTime = Date.now();

  // Cari index pertanyaan pertama yang belum dijawab
  for (let i = 0; i < items.length; i++) {
    if (items[i].is_visible && !items[i].jawaban) {
      currentIndex = i;
      break;
    }
  }

  function getActiveVisibleItems() {
    return items.filter(it => it.is_visible);
  }

  function renderCurrentQuestion() {
    const visibleList = getActiveVisibleItems();
    if (currentIndex >= visibleList.length) {
      finishJurnal();
      return;
    }

    const item = visibleList[currentIndex];
    startTime = Date.now();

    container.innerHTML = `
      <div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; background: linear-gradient(180deg, #F0FDF4 0%, #E8F6FB 100%);">
        
        <!-- Header & Progress -->
        <header style="padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.85); backdrop-filter: blur(8px); border-bottom: 2px solid rgba(74, 168, 216, 0.2);">
          <button id="btn-exit-jurnal" style="padding: 8px 16px; background: #F1F5F9; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; color: #475569; cursor: pointer;">
            &larr; Nanti Dulu
          </button>
          
          <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
            <div style="font-size: 14px; font-weight: 800; color: var(--color-navy);">
              Jurnal Gigi • Pertanyaan ${currentIndex + 1} dari ${visibleList.length}
            </div>
            <!-- Progress Dots -->
            <div style="display: flex; gap: 6px;">
              ${visibleList.map((_, idx) => `
                <div style="width: ${idx === currentIndex ? '24px' : '10px'}; height: 8px; border-radius: 4px; background: ${idx < currentIndex ? '#10B981' : (idx === currentIndex ? '#0D9488' : '#CBD5E1')}; transition: all 0.3s;"></div>
              `).join('')}
            </div>
          </div>

          <div style="font-size: 12px; font-weight: 700; color: #0D9488; background: #CCFBF1; padding: 6px 12px; border-radius: 20px;">
            Jujur & Santai
          </div>
        </header>

        <!-- Main Body: Mascot + Question + Speech Bubble -->
        <main style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; max-width: 700px; margin: 0 auto; width: 100%;">
          
          <!-- Maskot Dento dengan Balon Dialog -->
          <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 24px; width: 100%;">
            <div id="mascot-jurnal-host" style="width: 220px; height: 220px; margin-bottom: 8px;"></div>
            
            <!-- Speech Bubble -->
            <div id="jurnal-speech-bubble" style="background: #FFFFFF; border: 3px solid #0D9488; border-radius: 24px; padding: 20px 24px; position: relative; max-width: 540px; width: 100%; box-shadow: 0 8px 24px rgba(13, 148, 136, 0.15); text-align: center;">
              <p style="font-size: 19px; font-weight: 800; color: var(--color-navy); line-height: 1.4; margin: 0 0 6px 0;">
                ${item.teks}
              </p>
              <div id="feedback-text" style="font-size: 13px; font-weight: 600; color: #64748B;">
                Ceritakan dengan jujur ya, tidak ada jawaban yang salah.
              </div>
            </div>
          </div>

          <!-- Opsi Jawaban (Tombol Besar >= 56px, Inline SVG) -->
          <div id="options-container" style="display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 460px;">
            ${(item.opsi || []).map(op => `
              <button class="btn-opsi-jurnal" data-nilai="${op.nilai}" style="min-height: 58px; padding: 14px 20px; background: #FFFFFF; border: 2px solid #E2E8F0; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.04);">
                <div style="display: flex; align-items: center; gap: 14px;">
                  <div style="width: 36px; height: 36px; border-radius: 50%; background: #F0FDF4; border: 2px solid #10B981; display: flex; align-items: center; justify-content: center; color: #10B981;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/></svg>
                  </div>
                  <span style="font-size: 17px; font-weight: 800; color: var(--color-navy); text-align: left;">${op.label}</span>
                </div>
                <div style="color: #94A3B8;">&rarr;</div>
              </button>
            `).join('')}
          </div>

        </main>

        <!-- Footer / Bantuan -->
        <footer style="padding: 16px 24px; text-align: center; color: #64748B; font-size: 13px;">
          Jawaban tersimpan otomatis demi kelancaran belajarmu.
        </footer>

      </div>
    `;

    // Render maskot Dento dengan pose senang & tunjuk
    const host = container.querySelector('#mascot-jurnal-host');
    let mascot = createMascot(host, {
      expression: 'senang',
      pose: 'tunjuk',
      kebersihan: 90,
    }, { size: 220, animated: true });

    // Tombol keluar / nanti dulu
    container.querySelector('#btn-exit-jurnal')?.addEventListener('click', async () => {
      try {
        await api.post('/api/jurnal/lewati');
      } catch (e) {}
      router.navigate('home');
    });

    // Pilihan jawaban diklik
    const buttons = container.querySelectorAll('.btn-opsi-jurnal');
    buttons.forEach(btn => {
      btn.addEventListener('click', async () => {
        const nilai = btn.getAttribute('data-nilai');
        const ms = Date.now() - startTime;

        // Visual feedback
        buttons.forEach(b => {
          b.disabled = true;
          b.style.opacity = '0.6';
        });
        btn.style.opacity = '1';
        btn.style.borderColor = '#0D9488';
        btn.style.background = '#CCFBF1';

        sound.play('coin');

        let resp = null;
        try {
          resp = await api.post('/api/jurnal/jawab', {
            item_kode: item.kode,
            nilai: nilai,
            ms: ms,
          });
        } catch (err) {
          alert(err.message || 'Gagal menyimpan jawaban.');
          buttons.forEach(b => { b.disabled = false; b.style.opacity = '1'; });
          return;
        }

        // Simpan jawaban lokal untuk re-evaluasi tampil_jika
        item.jawaban = { nilai: nilai };

        // Evaluasi ulang tampil_jika untuk item-item berikutnya
        items.forEach(it => {
          if (it.tampil_jika) {
            const cond = it.tampil_jika;
            const parent = items.find(p => p.kode === cond.item);
            if (parent && parent.jawaban && cond.nilai_in.includes(parent.jawaban.nilai)) {
              it.is_visible = true;
            } else {
              it.is_visible = false;
            }
          }
        });

        // Tampilkan tanggapan Dento yang suportif dan netral selama 1.5 detik
        const bubble = container.querySelector('#jurnal-speech-bubble');
        const feedback = container.querySelector('#feedback-text');
        if (bubble && feedback && resp.tanggapan) {
          feedback.textContent = resp.tanggapan;
          feedback.style.color = '#0D9488';
          feedback.style.fontWeight = '800';
          feedback.style.fontSize = '15px';
        }

        // Update pose Dento menjadi senang & melambai
        if (mascot && mascot.update) {
          mascot.update({ expression: 'senang', pose: 'lambai' });
        }

        // Lanjut otomatis setelah 1.5 detik atau jika user klik bubble
        let advanced = false;
        const advanceNext = () => {
          if (!advanced) {
            advanced = true;
            currentIndex++;
            renderCurrentQuestion();
          }
        };

        const timer = setTimeout(advanceNext, 1400);
        bubble?.addEventListener('click', () => {
          clearTimeout(timer);
          advanceNext();
        }, { once: true });
      });
    });
  }

  async function finishJurnal() {
    container.innerHTML = `
      <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #E8F6FB;">
        <p style="font-size: 20px; color: var(--color-navy); font-weight: 700;">Menyimpan Jurnal Gigi...</p>
      </div>
    `;

    try {
      const res = await api.post('/api/jurnal/selesai');
      sound.play('levelUp');
      renderCompletionScreen(container, router, res.reward, res.rekomendasi, false);
    } catch (err) {
      if (err.status === 409) {
        renderCompletionScreen(container, router, null, null, true);
      } else {
        alert(err.message || 'Gagal menyelesaikan jurnal.');
        router.navigate('home');
      }
    }
  }

  renderCurrentQuestion();
  return {};
}

function renderCompletionScreen(container, router, reward, rekomendasi, alreadyFinished) {
  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; background: linear-gradient(180deg, #F0FDF4 0%, #E8F6FB 100%); padding: 24px; text-align: center;">
      
      <div style="background: #FFFFFF; border-radius: 28px; padding: 36px 28px; max-width: 520px; width: 100%; box-shadow: 0 16px 36px rgba(0,0,0,0.08); border: 2px solid #E2E8F0;">
        
        <div style="width: 80px; height: 80px; margin: 0 auto 16px auto; background: #CCFBF1; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #0D9488;">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        </div>

        <h1 style="color: var(--color-navy); font-size: 26px; font-weight: 800; margin: 0 0 8px 0;">
          ${alreadyFinished ? 'Jurnal Hari Ini Sudah Selesai!' : 'Terima Kasih Sudah Cerita!'}
        </h1>
        <p style="color: #64748B; font-size: 15px; line-height: 1.5; margin: 0 0 24px 0;">
          Kejujuranmu sangat berharga. Dento bangga kamu sudah menjaga dan memperhatikan kesehatan gigimu hari ini!
        </p>

        <!-- Hadiah Melapor -->
        ${reward ? `
          <div style="background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 18px; padding: 16px; margin-bottom: 24px; display: flex; justify-content: space-around; align-items: center;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
              <span style="font-size: 22px; font-weight: 800; color: #D97706;">+${reward.koin}</span>
              <span style="font-size: 12px; font-weight: 700; color: #64748B;">Koin Emas</span>
            </div>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
              <span style="font-size: 22px; font-weight: 800; color: #2563EB;">+${reward.xp}</span>
              <span style="font-size: 12px; font-weight: 700; color: #64748B;">XP Dento</span>
            </div>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
              <span style="font-size: 22px; font-weight: 800; color: #EC4899;">+${reward.kebahagiaan}</span>
              <span style="font-size: 12px; font-weight: 700; color: #64748B;">Kebahagiaan</span>
            </div>
          </div>
        ` : ''}

        <!-- Rekomendasi Adaptif Maksimal 1 -->
        ${rekomendasi ? `
          <div style="background: #F0FDF4; border: 2px solid #10B981; border-radius: 18px; padding: 20px; margin-bottom: 24px; text-align: left;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
              <span style="font-size: 12px; font-weight: 800; color: #065F46; background: #D1FAE5; padding: 4px 10px; border-radius: 12px;">Rekomendasi Spesial</span>
              <span style="font-size: 13px; font-weight: 700; color: #047857;">${rekomendasi.tipe === 'video' ? '🎬 Tontonan Seru' : '🎮 Permainan Seru'}</span>
            </div>
            <h3 style="font-size: 17px; font-weight: 800; color: var(--color-navy); margin: 0 0 6px 0;">${rekomendasi.judul}</h3>
            <p style="font-size: 14px; color: #374151; line-height: 1.4; margin: 0 0 16px 0;">${rekomendasi.deskripsi}</p>
            <div style="display: flex; gap: 10px;">
              <button id="btn-accept-rec" style="flex: 1; min-height: 44px; padding: 10px; background: #10B981; color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer;">
                ${rekomendasi.tipe === 'video' ? 'Ayo Tonton &rarr;' : 'Ayo Main &rarr;'}
              </button>
              <button id="btn-decline-rec" style="padding: 10px 16px; background: #E2E8F0; color: #475569; border: none; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer;">
                Nanti Saja
              </button>
            </div>
          </div>
        ` : ''}

        <!-- Tombol Tindakan -->
        <div style="display: flex; flex-direction: column; gap: 10px;">
          <button id="btn-to-ringkasan" style="min-height: 50px; padding: 12px 24px; background: #0D9488; color: #fff; border: none; border-radius: 14px; font-size: 16px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(13,148,136,0.3);">
            📊 Lihat Ringkasan Kebiasaan
          </button>
          <button id="btn-to-home" style="min-height: 46px; padding: 10px 24px; background: #F1F5F9; color: #475569; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer;">
            Kembali ke Rumah Dento
          </button>
        </div>

      </div>

    </div>
  `;

  if (rekomendasi) {
    container.querySelector('#btn-accept-rec')?.addEventListener('click', async () => {
      try {
        await api.post(`/api/jurnal/rekomendasi/${rekomendasi.id}/terima`);
      } catch (e) {}

      if (rekomendasi.tipe === 'video') {
        router.navigate(`videos?v=${rekomendasi.target_kode}&src=rekomendasi`);
      } else {
        router.navigate(`game/${rekomendasi.target_kode}`);
      }
    });

    container.querySelector('#btn-decline-rec')?.addEventListener('click', async () => {
      try {
        await api.post(`/api/jurnal/rekomendasi/${rekomendasi.id}/tolak`);
      } catch (e) {}
      container.querySelector('#btn-accept-rec')?.parentElement?.parentElement?.remove();
    });
  }

  container.querySelector('#btn-to-ringkasan')?.addEventListener('click', () => router.navigate('jurnal-ringkasan'));
  container.querySelector('#btn-to-home')?.addEventListener('click', () => router.navigate('home'));
}
