# Panduan Produksi & Integrasi Video Edukasi Gigi Dento

Dokumen panduan untuk Clara dalam memproduksi dan menempatkan video edukasi kesehatan gigi anak berbasis web pada sistem Dento.

---

## 1. Struktur Kategori Video

Sistem membagi video menjadi 4 kategori dengan fungsi dan penempatan yang jelas:

| Kategori | ID Video | Judul / Fokus | Durasi Target | Kuis Tantangan? | Hadiah Murid | Pemicu / Penempatan |
|---|---|---|---|---|---|---|
| **Inti** | `video-1` | Kenalan dengan Gigi Sehat Kita | 120 detik | Ya (2 butir) | +15 Koin, +20 XP | Menu Kelas, Terbuka pertama |
| **Inti** | `video-2` | Makanan Sahabat & Perusak Gigi | 150 detik | Ya (2 butir) | +15 Koin, +20 XP | Menu Kelas, Terbuka setelah V1 |
| **Inti** | `video-3` | Petualangan 2 Menit Menyikat Gigi | 120 detik | Ya (2 butir) | +15 Koin, +20 XP | Menu Kelas, Terbuka setelah V2 |
| **Inti** | `video-4` | Gigi Berlubang & Dokter Gigi | 140 detik | Ya (2 butir) | +15 Koin, +20 XP | Menu Kelas, Terbuka setelah V3 |
| **Mikro** | `M1-sikat-malam` | Kenapa Sikat Gigi Malam Wajib? | 45 detik | Tidak | +5 Koin, +5 XP | Rekomendasi Jurnal / Bantuan Game Sikat |
| **Mikro** | `M2-sikat-pagi` | Segar Seharian: Sikat Gigi Pagi | 45 detik | Tidak | +5 Koin, +5 XP | Rekomendasi Jurnal |
| **Mikro** | `M3-manis` | Makanan Manis & Kuman Gigi | 45 detik | Tidak | +5 Koin, +5 XP | Rekomendasi Jurnal / Bantuan Game Pilah Makanan |
| **Mikro** | `M4-kumur` | Jurus Kilat: Berkumur Air Putih | 35 detik | Tidak | +5 Koin, +5 XP | Rekomendasi Jurnal |
| **Pembuka**| `V0-pembuka` | Sambutan Dento & Petualangan Gigi | 40 detik | Tidak | Otomatis | Muncul sekali setelah Pre-Test selesai |
| **Penutup**| `VP-penutup` | Pesan Kelulusan & Senyum Sehat | 45 detik | Tidak | Otomatis | Muncul setelah Kuesioner Kepuasan selesai |

---

## 2. Spesifikasi Teknis Video

- **Format Berkas:** MP4 (H.264 video codec, AAC audio codec).
- **Resolusi:** 1280x720 (720p) atau 1920x1080 (1080p), rasio 16:9.
- **Ukuran Berkas:** Disarankan $\le 25$ MB per video agar cepat dimuat di jaringan sekolah.
- **Lokasi Penyimpanan Proyek:**
  - Video MP4: `public/media/videos/{nama_file}.mp4`
  - Poster Thumbnail: `public/media/videos/{nama_file}.png`
  - Subtitle VTT (opsional): `public/media/videos/{nama_file}.vtt`
- **Konfigurasi Data:** Terdaftar di `database/content/videos.json`.

---

## 3. Prinsip Bahasa & Penyampaian Anak SD

1. **Bahasa Hangat & Positif:** Hindari menakut-nakuti (jangan memakai gambar gigi berdarah atau jarum suntik seram).
2. **Ajakan Aksi Konkret:** Fokus pada apa yang *bisa dilakukan* anak ("Yuk berkumur air putih!", "Sikat melingkar seperti roda kereta").
3. **Validasi Ahli:** Semua naskah berstatus **DRAFT** dan wajib diverifikasi oleh dokter gigi pembimbing.
