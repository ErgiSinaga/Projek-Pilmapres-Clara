import { api } from '../api.js';
import { createMascot } from '../mascot/mascot.js';

export async function createCertificateScreen(container, router) {
  container.innerHTML = `
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #F8FAFC;">
      <p style="font-size: 18px; color: var(--color-navy); font-weight: 700;">Menyiapkan Sertifikat Pahlawan Gigi...</p>
    </div>
  `;

  let homeData = null;
  try {
    homeData = await api.get('/api/home');
  } catch (err) {
    router.navigate('login');
    return {};
  }

  const student = homeData.student || {};
  const pet = homeData.pet || { level: 1 };
  const todayDate = new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });

  container.innerHTML = `
    <style>
      @media print {
        body {
          background: #fff !important;
          margin: 0 !important;
          padding: 0 !important;
        }
        .no-print {
          display: none !important;
        }
        .certificate-wrapper {
          box-shadow: none !important;
          border: 8px double #B45309 !important;
          width: 100% !important;
          max-width: 100% !important;
          margin: 0 auto !important;
          page-break-inside: avoid;
        }
      }
    </style>

    <div style="min-height: 100vh; background: #F1F5F9; padding: 32px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box;">
      
      <!-- Tombol Aksi Layar (Disembunyikan saat Print) -->
      <div class="no-print" style="max-width: 860px; width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="btn-back-home" style="background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 10px; padding: 10px 18px; font-weight: 700; color: #475569; cursor: pointer;">
          &larr; Rumah Dento
        </button>
        <div style="display: flex; gap: 10px;">
          <button id="btn-print" style="background: #2563EB; color: #fff; border: none; border-radius: 10px; padding: 10px 22px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37,99,235,0.3);">
            <span>🖨️</span> Cetak / Simpan PDF
          </button>
        </div>
      </div>

      <!-- Bingkai Sertifikat Penghargaan -->
      <div class="certificate-wrapper" style="position: relative; max-width: 860px; width: 100%; background: #FFFFFF; border: 10px double #D97706; border-radius: 24px; padding: 48px 40px; box-shadow: 0 16px 40px rgba(0,0,0,0.08); text-align: center; box-sizing: border-box; overflow: hidden;">
        
        <!-- Ornamen Sudut Sertifikat -->
        <div style="position: absolute; top: 12px; left: 16px; font-size: 24px;">✨</div>
        <div style="position: absolute; top: 12px; right: 16px; font-size: 24px;">✨</div>
        <div style="position: absolute; bottom: 12px; left: 16px; font-size: 24px;">🌟</div>
        <div style="position: absolute; bottom: 12px; right: 16px; font-size: 24px;">🌟</div>

        <!-- Lambang Bintang Emas Atas -->
        <div style="font-size: 40px; margin-bottom: 8px;">🏆</div>

        <!-- Judul Sertifikat -->
        <div style="font-size: 14px; font-weight: 800; letter-spacing: 3px; color: #B45309; text-transform: uppercase; margin-bottom: 6px;">
          SERTIFIKAT PENGHARGAAN
        </div>
        <h1 style="font-size: 32px; font-weight: 800; color: var(--color-title); margin: 0 0 16px 0; letter-spacing: 1px;">
          PAHLAWAN GIGI SEHAT DENTO
        </h1>

        <p style="font-size: 16px; color: #475569; margin: 0 0 12px 0;">
          Diberikan secara istimewa kepada sahabat hebat:
        </p>

        <!-- Nama Sahabat / Kode Murid & Nama Dento -->
        <div style="margin: 20px auto; padding: 14px 28px; background: #FFFBEB; border-bottom: 3px solid #F59E0B; display: inline-block; min-width: 320px;">
          <div style="font-size: 28px; font-weight: 800; color: var(--color-navy); letter-spacing: 1px;">
            ${student.kode || 'S1-01'}
          </div>
          <div style="font-size: 16px; font-weight: 700; color: #B45309; margin-top: 4px;">
            Sahabat "${student.pet_name || 'Dento'}" • Capaian Level ${pet.level}
          </div>
        </div>

        <p style="font-size: 15px; color: #334155; line-height: 1.6; max-width: 640px; margin: 16px auto 28px auto;">
          Telah berhasil menyelesaikan rangkaian edukasi, perawatan gigi harian, kuis kesehatan gigi, dan tantangan dokter gigi cilik dengan penuh semangat untuk senyum masa depan yang bersinar!
        </p>

        <!-- Area Maskot & Cap Verifikasi -->
        <div style="display: flex; justify-content: space-around; align-items: center; margin-top: 24px; border-top: 1px solid #F1F5F9; padding-top: 20px;">
          
          <!-- Cap Stempel Resmi Pilmapres Dento -->
          <div style="border: 3px dashed #2563EB; border-radius: 50%; width: 110px; height: 110px; display: flex; flex-direction: column; align-items: center; justify-content: center; transform: rotate(-8deg); color: #2563EB;">
            <div style="font-size: 10px; font-weight: 800; letter-spacing: 1px;">PILMAPRES</div>
            <div style="font-size: 18px;">🩺⭐</div>
            <div style="font-size: 9px; font-weight: 800;">TERVERIFIKASI</div>
            <div style="font-size: 8px;">MAGELANG</div>
          </div>

          <!-- Maskot Mini Hero -->
          <div id="cert-mascot-box" style="width: 140px; height: 140px;"></div>

          <!-- Tanda Tangan & Tanggal -->
          <div style="text-align: center; color: #475569;">
            <div style="font-size: 13px; font-weight: 600; margin-bottom: 28px;">Magelang, ${todayDate}</div>
            <div style="font-size: 14px; font-weight: 800; color: var(--color-navy); border-top: 1.5px solid #64748B; padding-top: 6px; min-width: 160px;">
              Tim Edukasi Gigi Dento
            </div>
            <div style="font-size: 11px; color: #64748B;">Universitas & Sekolah Mitra</div>
          </div>

        </div>

      </div>

    </div>
  `;

  // Pasang hero maskot Dento di sertifikat
  const mascotBox = container.querySelector('#cert-mascot-box');
  createMascot(mascotBox, {
    expression: 'semangat',
    pose: 'atas',
    kebersihan: 100,
    equipped: homeData.equipped || {}
  }, { size: 140, animated: false });

  // Event handlers
  container.querySelector('#btn-back-home').addEventListener('click', () => {
    router.navigate('home');
  });

  container.querySelector('#btn-print').addEventListener('click', () => {
    window.print();
  });

  return {};
}
