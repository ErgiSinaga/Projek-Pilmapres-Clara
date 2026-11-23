import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';
import { sound } from '../audio.js';

export async function createVideosScreen(container, router, params = {}) {
  let videoList = [];
  let currentTab = 'inti'; // 'inti' | 'mikro'
  let progressInterval = null;
  let simulatedSeconds = 0;
  const initialVideoId = params.v || null;
  const initialSource = params.src || 'kelas';

  container.innerHTML = `
    <div style="min-height: 100vh; background: #F8FAFC; padding: 24px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      <div style="max-width: 840px; width: 100%; background: #ffffff; padding: 32px 24px; border-radius: 28px; box-shadow: 0 12px 32px rgba(37, 67, 92, 0.08); border: 2px solid #E8F6FB; text-align: center;">
        <p style="color:#64748B; font-size:18px;">Membuka Ruang Kelas Dokter Gigi...</p>
      </div>
    </div>
  `;

  async function loadVideos() {
    try {
      const resp = await api.get('/api/videos');
      videoList = resp.videos || [];

      // Jika ada request video khusus via URL query params (misal ?v=M1-sikat-malam)
      if (initialVideoId) {
        const targetVideo = videoList.find(v => v.id === initialVideoId);
        if (targetVideo) {
          playVideo(targetVideo, initialSource);
          return;
        }
      }

      renderVideoList();
    } catch (e) {
      container.innerHTML = `<p style="text-align:center; padding:40px;">Gagal memuat materi video.</p>`;
    }
  }

  function renderVideoList() {
    const wrapper = container.firstElementChild.firstElementChild;
    const coreVideos = videoList.filter(v => (v.tipe || 'inti') === 'inti');
    const microVideos = videoList.filter(v => v.tipe === 'mikro');
    const displayedVideos = currentTab === 'inti' ? coreVideos : microVideos;

    wrapper.innerHTML = `
      <!-- Header -->
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 20px;">
        <button id="btn-back-home" style="background: #F1F5F9; border: none; border-radius: 10px; padding: 8px 16px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="font-size: 20px; font-weight: 800; color: var(--color-title);">
          Kelas Dokter Gigi Cilik
        </div>
        <div id="video-mascot-box" style="width: 80px; height: 80px;"></div>
      </div>

      <!-- Tab Navigasi: Pelajaran Inti vs Video Singkat -->
      <div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid #E2E8F0; padding-bottom: 12px;">
        <button id="tab-inti" style="padding: 10px 20px; border-radius: 12px; border: none; font-size: 15px; font-weight: 800; cursor: pointer; background: ${currentTab === 'inti' ? 'var(--color-sky-dark)' : '#F1F5F9'}; color: ${currentTab === 'inti' ? '#ffffff' : '#64748B'};">
          🎬 Pelajaran Inti (${coreVideos.length})
        </button>
        <button id="tab-mikro" style="padding: 10px 20px; border-radius: 12px; border: none; font-size: 15px; font-weight: 800; cursor: pointer; background: ${currentTab === 'mikro' ? '#0D9488' : '#F1F5F9'}; color: ${currentTab === 'mikro' ? '#ffffff' : '#64748B'};">
          ⚡ Video Singkat (${microVideos.length})
        </button>
      </div>

      <p style="color: #475569; font-size: 14px; margin: 0 0 20px 0; text-align: left;">
        ${currentTab === 'inti'
          ? 'Tonton video seru bersama dokter gigi untuk membuka tantangan dan kuis berhadiah koin & XP!'
          : 'Tonton video tips kilat 30-45 detik yang direkomendasikan Dento untuk gigimu!'}
      </p>

      <!-- Daftar Kartu Video -->
      <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px; text-align: left;">
        ${displayedVideos.map((v, idx) => {
          const isLocked = !v.unlocked;
          const isDone = v.is_completed;

          return `
            <div class="video-card ${isLocked ? 'locked' : 'unlocked'}" data-id="${v.id}"
              style="display: flex; gap: 16px; align-items: center; padding: 16px 20px; border-radius: 18px; border: 2px solid ${isDone ? '#10B981' : (isLocked ? '#E2E8F0' : 'var(--color-sky)')}; background: ${isDone ? '#F0FDF4' : (isLocked ? '#F8FAFC' : '#FFFFFF')}; opacity: ${isLocked ? '0.65' : '1'}; transition: all 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
              
              <div style="width: 52px; height: 52px; border-radius: 14px; background: ${isDone ? '#DCFCE7' : (isLocked ? '#E2E8F0' : '#E0F2FE')}; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                ${isDone ? '✅' : (isLocked ? '🔒' : '▶️')}
              </div>

              <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                  <span style="font-size: 11px; font-weight: 800; color: #fff; background: ${v.tipe === 'mikro' ? '#0D9488' : 'var(--color-sky-dark)'}; padding: 2px 8px; border-radius: 6px;">
                    ${v.tipe === 'mikro' ? 'Video Singkat' : v.tp}
                  </span>
                  <span style="font-size: 12px; font-weight: 700; color: #64748B;">
                    Durasi: ${v.durasi_detik} detik
                  </span>
                  ${isDone ? `
                    <span style="font-size: 11px; font-weight: 800; color: #16A34A; background: #DCFCE7; padding: 2px 8px; border-radius: 6px;">
                      Sudah Ditonton
                    </span>
                  ` : ''}
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--color-navy); margin: 0 0 4px 0; line-height: 1.3;">
                  ${v.tipe === 'inti' ? `${v.urutan}. ` : ''}${v.judul}
                </h3>
                <p style="font-size: 13px; color: #64748B; margin: 0; line-height: 1.4;">
                  ${v.deskripsi}
                </p>
              </div>

              <div>
                ${isLocked ? `
                  <button disabled style="padding: 10px 16px; border-radius: 10px; background: #CBD5E1; color: #64748B; border: none; font-weight: 700; cursor: not-allowed; font-size: 14px;">
                    Terkunci
                  </button>
                ` : `
                  <button class="btn-watch" data-id="${v.id}" style="padding: 10px 20px; border-radius: 12px; background: ${v.tipe === 'mikro' ? '#0D9488' : '#2563EB'}; color: #fff; border: none; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                    ${isDone ? 'Tonton Ulang' : 'Mulai Tonton'}
                  </button>
                `}
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;

    // Render mini mascot
    const mascotBox = wrapper.querySelector('#video-mascot-box');
    if (mascotBox) {
      createMascot(mascotBox, { expression: 'senang', pose: 'sikat', kebersihan: 90 }, { size: 80, track: false });
    }

    wrapper.querySelector('#btn-back-home')?.addEventListener('click', () => router.navigate('home'));

    wrapper.querySelector('#tab-inti')?.addEventListener('click', () => {
      currentTab = 'inti';
      renderVideoList();
    });

    wrapper.querySelector('#tab-mikro')?.addEventListener('click', () => {
      currentTab = 'mikro';
      renderVideoList();
    });

    wrapper.querySelectorAll('.btn-watch').forEach(btn => {
      btn.addEventListener('click', () => {
        const vid = btn.dataset.id;
        const video = videoList.find(v => v.id === vid);
        if (video) playVideo(video, 'kelas');
      });
    });
  }

  function playVideo(video, sumber = 'kelas') {
    const wrapper = container.firstElementChild.firstElementChild;
    const durasi = video.durasi_detik || 120;
    const minRequired = Math.round(durasi * 0.9);
    simulatedSeconds = 0;
    const isMicro = video.tipe === 'mikro' || video.tipe === 'pembuka' || video.tipe === 'penutup';

    wrapper.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F1F5F9; padding-bottom: 14px; margin-bottom: 20px;">
        <button id="btn-back-list" style="background: #F1F5F9; border: none; border-radius: 10px; padding: 8px 16px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Kembali ke Daftar
        </button>
        <div style="font-size: 17px; font-weight: 800; color: var(--color-title);">
          ${video.judul}
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #0D9488; background: #CCFBF1; padding: 4px 10px; border-radius: 12px;">
          ${video.tp || 'Edukasi Gigi'}
        </div>
      </div>

      <!-- Player Frame -->
      <div style="position: relative; background: #000; border-radius: 20px; overflow: hidden; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
        ${video.file_exists ? `
          <video id="player-video" playsinline style="width: 100%; height: auto; display: block;" poster="${video.poster_url || ''}">
            <source src="${video.file_url}" type="video/mp4">
          </video>
        ` : `
          <div style="padding: 48px 24px; background: linear-gradient(135deg, #1E3A8A, #0284C7); color: #fff; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">🩺🎬🦷</div>
            <h3 style="font-size: 22px; font-weight: 800; margin: 0 0 10px 0;">Video Sedang Disiapkan Dokter Gigi</h3>
            <p style="font-size: 15px; color: #E0F2FE; max-width: 480px; margin: 0 auto 24px auto; line-height: 1.4;">
              Materi video singkat ini mengedukasi tentang <strong>${video.judul}</strong>. Selesaikan video untuk mendapatkan koin dan apresiasi Dento!
            </p>
            <div style="display: inline-flex; flex-direction: column; gap: 10px;">
              <button id="btn-simulate-watch" style="min-height: 52px; padding: 12px 28px; background: #10B981; color: #fff; border: 2px solid #059669; border-radius: 14px; font-size: 16px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
                ✅ Tandai Sudah Tonton Bersama Guru
              </button>
            </div>
          </div>
        `}

        <!-- Bilah Kontrol -->
        <div style="background: rgba(15, 23, 42, 0.95); padding: 12px 18px; display: flex; align-items: center; gap: 14px; color: #fff;">
          <button id="btn-play-pause" style="width: 44px; height: 44px; border-radius: 50%; background: #2563EB; border: none; color: #fff; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            ▶️
          </button>

          <button id="btn-rewind" style="background: #334155; border: none; color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
            ⏪ -10s
          </button>

          <div style="flex: 1; height: 10px; background: #475569; border-radius: 5px; overflow: hidden; position: relative;">
            <div id="progress-bar-fill" style="width: 0%; height: 100%; background: #38BDF8; transition: width 0.3s;"></div>
          </div>

          <div id="time-display" style="font-size: 13px; font-weight: 700; font-family: monospace; color: #CBD5E1;">
            0:00 / ${Math.floor(durasi / 60)}:${(durasi % 60) < 10 ? '0' : ''}${durasi % 60}
          </div>
        </div>
      </div>

      <!-- Kotak Selesai untuk Video Mikro / Inti -->
      <div id="challenge-prompt-box" style="display: none; background: #ECFDF5; border: 2.5px solid #10B981; border-radius: 20px; padding: 20px; margin-bottom: 20px; text-align: center;">
        <h3 style="font-size: 20px; font-weight: 800; color: #065F46; margin: 0 0 8px 0;">
          🎉 Video Selesai Ditonton!
        </h3>
        ${isMicro ? `
          <p style="font-size: 15px; color: #047857; margin: 0 0 16px 0;">
            Hebat sekali! Kamu telah mendapatkan <strong>+5 Koin Hadiah & +5 XP</strong>!
          </p>
          <button id="btn-back-to-list-done" style="min-height: 48px; padding: 12px 32px; background: #10B981; color: #fff; border: none; border-radius: 14px; font-size: 16px; font-weight: 800; cursor: pointer;">
            Lanjut &rarr;
          </button>
        ` : `
          <p style="font-size: 15px; color: #047857; margin: 0 0 16px 0;">
            Ayo selesaikan kuis tantangan singkat untuk mendapatkan <strong>+15 Koin & +20 XP</strong>!
          </p>
          <button id="btn-start-challenge" style="min-height: 52px; padding: 12px 32px; background: #10B981; color: #fff; border: 2px solid #059669; border-radius: 14px; font-size: 17px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);">
            Buka Kuis Tantangan &rarr;
          </button>
        `}
      </div>

      <!-- Modal Kuis Tantangan (Untuk Video Inti) -->
      <div id="challenge-modal" style="display: none; position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #fff; border-radius: 24px; padding: 28px; max-width: 540px; width: 100%; max-height: 90vh; overflow-y: auto; text-align: left; box-shadow: 0 16px 40px rgba(0,0,0,0.2);">
          <h2 style="font-size: 22px; font-weight: 800; color: var(--color-title); margin: 0 0 14px 0;">
            Kuis Tantangan: ${video.judul}
          </h2>
          <div id="challenge-questions-list"></div>
          <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
            <button id="btn-close-modal" style="padding: 10px 18px; border-radius: 10px; background: #F1F5F9; border: none; font-weight: 700; color: #475569; cursor: pointer;">
              Tutup
            </button>
            <button id="btn-submit-challenge" style="padding: 12px 24px; border-radius: 12px; background: #2563EB; color: #fff; border: none; font-weight: 800; cursor: pointer;">
              Kirim Jawaban &rarr;
            </button>
          </div>
        </div>
      </div>
    `;

    wrapper.querySelector('#btn-back-list').addEventListener('click', () => {
      clearInterval(progressInterval);
      renderVideoList();
    });

    const videoEl = wrapper.querySelector('#player-video');
    const playPauseBtn = wrapper.querySelector('#btn-play-pause');
    const rewindBtn = wrapper.querySelector('#btn-rewind');
    const progressFill = wrapper.querySelector('#progress-bar-fill');
    const timeDisplay = wrapper.querySelector('#time-display');
    const challengeBox = wrapper.querySelector('#challenge-prompt-box');
    const simulateBtn = wrapper.querySelector('#btn-simulate-watch');

    if (simulateBtn) {
      simulateBtn.addEventListener('click', async () => {
        try {
          await api.post(`/api/videos/${video.id}/complete`, { teacher_assisted: true, sumber: sumber });
          video.is_completed = true;
          challengeBox.style.display = 'block';
          sound.playEffect('jump');
        } catch (e) {
          alert('Gagal menyelesaikan video: ' + (e.message || 'Error'));
        }
      });
    }

    if (videoEl) {
      playPauseBtn.addEventListener('click', () => {
        if (videoEl.paused) {
          videoEl.play();
          playPauseBtn.textContent = '⏸️';
        } else {
          videoEl.pause();
          playPauseBtn.textContent = '▶️';
        }
      });

      rewindBtn.addEventListener('click', () => {
        videoEl.currentTime = Math.max(0, videoEl.currentTime - 10);
      });

      videoEl.addEventListener('timeupdate', async () => {
        const cur = Math.floor(videoEl.currentTime);
        const dur = Math.floor(videoEl.duration || durasi);
        const pct = Math.min(100, (cur / dur) * 100);

        if (progressFill) progressFill.style.width = `${pct}%`;
        if (timeDisplay) {
          const curM = Math.floor(cur / 60);
          const curS = cur % 60;
          const durM = Math.floor(dur / 60);
          const durS = dur % 60;
          timeDisplay.textContent = `${curM}:${curS < 10 ? '0' : ''}${curS} / ${durM}:${durS < 10 ? '0' : ''}${durS}`;
        }

        if (cur > 0 && cur % 5 === 0 && cur > simulatedSeconds) {
          simulatedSeconds = cur;
          api.post(`/api/videos/${video.id}/progress`, { detik: cur, sumber: sumber });
        }

        if (cur >= minRequired && !video.is_completed) {
          video.is_completed = true;
          await api.post(`/api/videos/${video.id}/complete`, { sumber: sumber });
          challengeBox.style.display = 'block';
          sound.playEffect('jump');
        }
      });
    }

    wrapper.querySelector('#btn-back-to-list-done')?.addEventListener('click', () => {
      renderVideoList();
    });

    // Modal Tantangan untuk video inti
    const challengeModal = wrapper.querySelector('#challenge-modal');
    const openChallengeBtn = wrapper.querySelector('#btn-start-challenge');
    const closeChallengeBtn = wrapper.querySelector('#btn-close-modal');
    const submitChallengeBtn = wrapper.querySelector('#btn-submit-challenge');
    const challengeList = wrapper.querySelector('#challenge-questions-list');

    openChallengeBtn?.addEventListener('click', () => {
      challengeModal.style.display = 'flex';
      renderChallengeQuestions();
    });

    closeChallengeBtn?.addEventListener('click', () => {
      challengeModal.style.display = 'none';
    });

    let chosenAnswers = {};

    function renderChallengeQuestions() {
      const qs = video.tantangan || [];
      challengeList.innerHTML = qs.map((q, idx) => `
        <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 16px; padding: 16px; margin-bottom: 14px;">
          <div style="font-size: 15px; font-weight: 800; color: var(--color-navy); margin-bottom: 10px;">
            ${idx + 1}. ${q.pertanyaan}
          </div>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            ${q.opsi.map((opt, optIdx) => `
              <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: #fff; border: 1.5px solid #CBD5E1; cursor: pointer; font-size: 14px; font-weight: 600; color: #334155;">
                <input type="radio" name="q_${idx}" value="${optIdx}" style="accent-color: #2563EB; width: 18px; height: 18px;">
                <span>${opt}</span>
              </label>
            `).join('')}
          </div>
        </div>
      `).join('');

      challengeList.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
          const qIdx = parseInt(e.target.name.replace('q_', ''), 10);
          chosenAnswers[qIdx] = parseInt(e.target.value, 10);
        });
      });
    }

    submitChallengeBtn?.addEventListener('click', async () => {
      const qs = video.tantangan || [];
      if (Object.keys(chosenAnswers).length < qs.length) {
        alert('Jawab semua pertanyaan tantangan terlebih dahulu ya!');
        return;
      }

      try {
        const resp = await api.post(`/api/videos/${video.id}/challenge`, { answers: chosenAnswers });
        challengeModal.style.display = 'none';
        sound.playEffect('jump');
        alert(`🎉 Luar biasa! Tantangan selesai dengan skor ${resp.skor} dari ${resp.total}.\nHadiah: +${resp.koin_didapat} Koin, +${resp.xp_didapat} XP, +${resp.kebersihan_tambah}% Kebersihan!`);
        await loadVideos();
      } catch (err) {
        alert('Gagal mengirim jawaban: ' + (err.message || 'Error'));
      }
    });
  }

  await loadVideos();

  return {
    destroy() {
      if (progressInterval) clearInterval(progressInterval);
    }
  };
}
